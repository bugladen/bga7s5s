<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03073;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_03073_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03073';
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling is the availability gate. Calculate does not check it again.
            'adds one thrust after a gamble' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03073(), Game::LOCATION_HAND, 1);
                $maneuver = $risk->getManeuvers()[0];
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'gambled');

                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(1, $calc->thrust, 'thrust');
                Assert::count(1, $calc->explanations, 'explanation');
            },
        ];
    }
}
