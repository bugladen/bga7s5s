<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04046;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04046;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_04046_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04046';
    }

    public function tests(): array
    {
        return [
            // WHY: Duelist actor only. +1 Parry on calculate.
            'adds one parry for a duelist actor' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04046(), Game::LOCATION_DUELING_LINE, 1);
                $duelist = $world->placeCharacter(new _04043(), Game::LOCATION_CITY_DOCKS, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Maneuver_04046 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'no actor');

                $world->theah->duelActor = $hero;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a duelist');

                $world->theah->duelActor = $duelist;
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'duelist');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->parry = 1;
                $world->fireOn($risk, $calc);
                Assert::same(2, $calc->parry, '+1 parry');
            },
        ];
    }
}
