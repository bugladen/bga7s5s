<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04004;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04004_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04004';
    }

    public function tests(): array
    {
        return [
            'is a scheme city action that needs a performer' => function () {
                $action = new Action_04004();
                Assert::instanceOf(SchemeCityAction::class, $action, 'SchemeCityAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Duelist is a trait gate. A destination is any other city location that
            // already holds a wounded enemy. The scheme must be at Home.
            'available for a duelist who can reach a wounded enemy' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04004(), Game::LOCATION_PLAYER_HOME, 1);
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $wounded = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $wounded->Wounds = 1;
                /** @var Action_04004 $action */
                $action = $scheme->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');

                $wounded->Wounds = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unwounded');
                $wounded->Wounds = 1;
                $wounded->ControllerId = 1;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'ally');
                $wounded->ControllerId = 2;
                $duelist->ModifiedTraits = ['Hero'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not a duelist');
                $scheme->Location = Game::LOCATION_CITY_DOCKS;
                $duelist->ModifiedTraits = ['Duelist'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'scheme left home');
            },

            'the chosen location is a move that does not engage' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04004(), Game::LOCATION_PLAYER_HOME, 1);
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $wounded = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $wounded->Wounds = 1;
                /** @var Action_04004 $action */
                $action = $scheme->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $duelist->Id);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                Assert::same('04004', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_04004,
                    '',
                    [Game::LOCATION_CITY_FORUM]
                );
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::false($move->engage, 'no engage');
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same('locationChosen', $world->game->gamestate->transitions[0], 'next');
            },
        ];
    }
}
