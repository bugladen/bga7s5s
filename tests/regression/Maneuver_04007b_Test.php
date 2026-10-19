<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04007b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_04007b_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04007b';
    }

    public function tests(): array
    {
        return [
            'subtracts a parry and adds two thrust for a duelist' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04007(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $actor;
                /** @var Maneuver_04007b $b */
                $b = $risk->getManeuvers()[1];
                Assert::true($b->isAvailableToPlayer(1, $world->theah), 'duelist');
                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $b->Id;
                $calc->parry = 2;
                $calc->thrust = 3;
                $calc->theah = $world->theah;
                $b->handleEvent($calc);
                Assert::same(1, $calc->parry, 'minus 1');
                Assert::same(5, $calc->thrust, 'plus 2');
            },
        ];
    }
}
