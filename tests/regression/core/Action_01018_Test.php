<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01018;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;

class Action_01018_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01018';
    }

    public function tests(): array
    {
        return [
            'available when Angelo is in city' => function () {
                $world = new TestWorld();
                $angelo = $world->placeCharacter(new _01018(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01018 $action */
                $action = $angelo->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'in city');
            },

            'unavailable at Player Home' => function () {
                $world = new TestWorld();
                $angelo = $world->placeCharacter(new _01018(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01018 $action */
                $action = $angelo->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'at Home');
            },

            // WHY: City Action resolves immediately on trigger — no choose-target state
            'trigger destroys Angelo and draws' => function () {
                $world = new TestWorld();
                $angelo = $world->placeCharacter(new _01018(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01018 $action */
                $action = $angelo->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same($angelo->Id, $world->theah->queuedOfType(EventCharacterDestroyed::class)[0]->characterId, 'destroy');
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'draw');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
