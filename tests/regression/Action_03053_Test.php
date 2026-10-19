<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03053;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;

class Action_03053_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03053';
    }

    public function tests(): array
    {
        return [
            'is a scheme city action that needs a performer' => function () {
                $action = new Action_03053();
                Assert::instanceOf(SchemeCityAction::class, $action, 'scheme city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Cost is a Renown from the player score. Performers are only those whose
            // location can still be claimed. A claim that fails after pay still draws for
            // each opponent and resolves.
            'spends a renown, claims, and each opponent draws' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03053(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_03053 $action */
                $action = $scheme->getActions()[0];
                $world->game->setPlayerReknown(1, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no renown');

                $world->game->setPlayerReknown(1, 2);
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                Assert::count(0, $action->getPerformersForAction(1, $world->theah), 'unclaimable');
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, true);
                Assert::same([$performer->Id], array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'performer');

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                Assert::same(1, $world->theah->queuedOfType(EventPlayerLosesReknown::class)[0]->amount, 'spent');
                $claim = $world->theah->queuedOfType(EventLocationClaimed::class)[0];
                Assert::same(1, $claim->playerId, 'you claim');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claim->location, 'docks');
                Assert::same([2], array_map(fn ($e) => $e->playerId, $world->theah->queuedOfType(EventCardDrawn::class)), 'opponent draws');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $world->theah->queuedEvents = [];
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'cannot claim');
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'still draws');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'still resolved');
            },

            'a performer who left the city throws before the spend' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03053(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_PLAYER_HOME, 1);
                $action = $scheme->getActions()[0];
                $world->game->setPlayerReknown(1, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $threw = false;
                try {
                    $triggered->theah = $world->theah;
                    $action->handleEvent($triggered);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'home');
                Assert::count(0, $world->theah->queuedOfType(EventPlayerLosesReknown::class), 'no spend');
            },
        ];
    }
}
