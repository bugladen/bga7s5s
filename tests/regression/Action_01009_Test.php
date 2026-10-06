<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01009;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01009_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01009';
    }

    public function tests(): array
    {
        return [
            'available with en garde Cirilo in city and uncontrolled Mercenary here' => function () {
                $world = new TestWorld();
                $cirilo = $world->placeCharacter(new _01009(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Available Merc', ['Mercenary']),
                    Game::LOCATION_CITY_DOCKS,
                    0 // uncontrolled
                );

                /** @var Action_01009 $action */
                $action = $cirilo->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Cirilo is engaged' => function () {
                $world = new TestWorld();
                $cirilo = $world->placeCharacter(new _01009(), Game::LOCATION_CITY_DOCKS, 1);
                $cirilo->Engaged = true;
                $world->placeCharacter(
                    new GenericCharacter('Available Merc', ['Mercenary']),
                    Game::LOCATION_CITY_DOCKS,
                    0
                );

                /** @var Action_01009 $action */
                $action = $cirilo->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'must Engage Cirilo — already engaged');
            },

            'unavailable when Mercenary is at a different location' => function () {
                $world = new TestWorld();
                $cirilo = $world->placeCharacter(new _01009(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Far Merc', ['Mercenary']),
                    Game::LOCATION_CITY_FORUM,
                    0
                );

                /** @var Action_01009 $action */
                $action = $cirilo->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Merc must be at this location');
            },

            'trigger engages Cirilo, sets CIRILO recruit type, transitions 01009' => function () {
                $world = new TestWorld();
                $cirilo = $world->placeCharacter(new _01009(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01009 $action */
                $action = $cirilo->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::CIRILO_RECRUIT_TYPE, $world->game->globals->get(Game::RECRUIT_TYPE), 'recruit type');
                Assert::same($cirilo->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage queued');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('01009', $transitions[0]->transition, 'transition');
            },
        ];
    }
}
