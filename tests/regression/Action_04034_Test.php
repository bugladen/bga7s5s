<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04034;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04034_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04034';
    }

    public function tests(): array
    {
        return [
            // WHY: The performer must be in a city location an opponent controls. That
            // opponent chooses. Lose control uncontrols it. Decline wounds opposing
            // characters there, which includes the controller's characters, not yours.
            'the controller may yield, or opposing characters are wounded' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04034(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04034 $action */
                $action = $scheme->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'opponent controls the docks');

                $trigger = new EventActionTriggered();
                $trigger->actionId = $action->Id;
                $trigger->playerId = 1;
                $trigger->theah = $world->theah;
                $action->handleEvent($trigger);
                $offer = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04034', $offer->transition, 'their choice');
                Assert::same(2, $offer->playerId, 'controller chooses');

                $world->game->activePlayerId = 2;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04034, '', 1);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class)[0]->location, 'yielded');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wounds');

                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04034, '', 2);
                $wounded = array_map(fn($event) => $event->characterId, $world->theah->queuedOfType(EventCharacterBeingWounded::class));
                Assert::same([$foe->Id], $wounded, 'opposing characters, not your performer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $world->game->activePlayerId = 1;
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04034, '', 1);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'only the controller');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'you already control it');
            },
        ];
    }
}
