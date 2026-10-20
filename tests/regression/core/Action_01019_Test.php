<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01019;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01019_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01019';
    }

    public function tests(): array
    {
        return [
            'available in city with another character at location' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable alone in city' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'isValidTargetForAbility rejects self and off-location' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                $local = $world->placeCharacter(new GenericCharacter('Local'), Game::LOCATION_CITY_DOCKS, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                Assert::false($action->isValidTargetForAbility($world->game, $buratino)[0], 'self');
                Assert::true($action->isValidTargetForAbility($world->game, $local)[0], 'local');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'far');
            },

            'trigger queues transition 01019' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01019', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'act destroys Buratino and wounds target' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01019,
                    'highDramaPhase01019',
                    $foe->Id
                );

                Assert::same($buratino->Id, $world->theah->queuedOfType(EventCharacterDestroyed::class)[0]->characterId, 'destroy');
                Assert::same($foe->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wound');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'nextState');
            },
        ];
    }
}
