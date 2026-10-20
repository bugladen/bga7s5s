<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04060;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_04060_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04060';
    }

    public function tests(): array
    {
        return [
            // WHY: "Instead" is exclusive. Uncontrolled (Control_ global, the actor's
            // location) is Riposte only. A controlled site is Thrust only.
            'adds riposte only while the duel location is uncontrolled' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04060(), Game::LOCATION_DUELING_LINE, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $actor;
                /** @var Maneuver_04060 $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->riposte = 0;
                $calc->thrust = 0;
                $world->fireOn($risk, $calc);
                Assert::same(1, $calc->riposte, 'uncontrolled riposte');
                Assert::same(0, $calc->thrust, 'not thrust');

                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 2);
                $calc->riposte = 0;
                $calc->thrust = 0;
                $world->fireOn($risk, $calc);
                Assert::same(0, $calc->riposte, 'not riposte');
                Assert::same(1, $calc->thrust, 'controlled thrust');
            },
        ];
    }
}
