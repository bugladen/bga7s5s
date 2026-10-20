<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04048b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_04048b_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04048b';
    }

    public function tests(): array
    {
        return [
            // WHY: +1 Riposte, then unclaim only when Control_ is non-zero. An
            // uncontrolled site returns before the can* check. A blocked flag queues nothing.
            'unclaims the duel location only while it is controlled' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04048(), Game::LOCATION_DUELING_LINE, 1);
                $duelist = $world->placeCharacter(new _04043(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $duelist;
                /** @var Maneuver_04048b $maneuver */
                $maneuver = $risk->getManeuvers()[1];

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->riposte = 0;
                $world->fireOn($risk, $calc);
                Assert::same(1, $calc->riposte, '+1 riposte');

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $world->fireOn($risk, $resolve);
                Assert::count(0, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class), 'already uncontrolled');

                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 2);
                $world->fireOn($risk, $resolve);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class)[0]->location, 'controlled');

                $world->theah->takeQueuedEvents();
                $world->theah->setLocationCanBecomeUncontrolled(Game::LOCATION_CITY_DOCKS, false);
                $world->fireOn($risk, $resolve);
                Assert::count(0, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class), 'cannot become uncontrolled');
            },
        ];
    }
}
