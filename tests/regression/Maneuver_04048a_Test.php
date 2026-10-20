<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04048a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_04048a_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04048a';
    }

    public function tests(): array
    {
        return [
            // WHY: +1 Riposte always for a Duelist. Claim uses the actor's location and
            // the Control_ global, and only when that controller is 0. A CanBeClaimed
            // block queues nothing.
            'claims the duel location only while it is uncontrolled' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04048(), Game::LOCATION_DUELING_LINE, 1);
                $duelist = $world->placeCharacter(new _04043(), Game::LOCATION_CITY_DOCKS, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Maneuver_04048a $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $world->theah->duelActor = $hero;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a duelist');
                $world->theah->duelActor = $duelist;
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'duelist');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->riposte = 0;
                $world->fireOn($risk, $calc);
                Assert::same(1, $calc->riposte, '+1 riposte');

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $world->fireOn($risk, $resolve);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationClaimed::class)[0]->location, 'uncontrolled');

                $world->theah->takeQueuedEvents();
                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 2);
                $world->fireOn($risk, $resolve);
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'already controlled');

                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 0);
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                $world->fireOn($risk, $resolve);
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'cannot be claimed');
            },
        ];
    }
}
