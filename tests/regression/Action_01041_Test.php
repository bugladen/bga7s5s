<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01041;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01041_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01041';
    }

    public function tests(): array
    {
        return [
            'available vs opposing non-Leader with equal or lower Influence' => function () {
                $world = new TestWorld();
                $rosine = $world->placeCharacter(new _01041(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01041 $action */
                $action = $rosine->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable vs Leader or higher Influence' => function () {
                $world = new TestWorld();
                $rosine = $world->placeCharacter(new _01041(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01041 $action */
                $action = $rosine->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'leader');

                $high = $world->placeCharacter(new GenericCharacter('High Inf'), Game::LOCATION_CITY_DOCKS, 2);
                $high->ModifiedInfluence = 5;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'higher inf');
            },

            'trigger queues transition 01041' => function () {
                $world = new TestWorld();
                $rosine = $world->placeCharacter(new _01041(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01041 $action */
                $action = $rosine->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01041', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'act engages non-Sorcerer target' => function () {
                $world = new TestWorld();
                $rosine = $world->placeCharacter(new _01041(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01041 $action */
                $action = $rosine->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01041,
                    'highDramaPhase01041',
                    $foe->Id
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no home');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'act engages Sorcerer and moves Home' => function () {
                $world = new TestWorld();
                $rosine = $world->placeCharacter(new _01041(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(
                    new GenericCharacter('Sorcerer', ['Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );

                /** @var Action_01041 $action */
                $action = $rosine->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01041,
                    'highDramaPhase01041',
                    $foe->Id
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'home');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'to home');
            },
        ];
    }
}
