<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03058;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_03058_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03058';
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling is the only availability gate. Calculate adds one Parry and one
            // Thrust per opposing character. Uncontrolled characters do not count. Zero adds nothing.
            'adds parry and thrust for each opposing character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03058(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Foe2'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Loose'), Game::LOCATION_CITY_DOCKS, 0);
                $maneuver = $risk->getManeuvers()[0];
                $world->theah->duelActor = $actor;

                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'gambled');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->parry, 'parry');
                Assert::same(2, $calc->thrust, 'thrust');
                Assert::count(1, $calc->explanations, 'explanation');

                $actor->Location = Game::LOCATION_CITY_FORUM;
                $calc->parry = 0;
                $calc->thrust = 0;
                $calc->explanations = [];
                $maneuver->handleEvent($calc);
                Assert::same(0, $calc->parry, 'none');
                Assert::count(0, $calc->explanations, 'no explanation');
            },
        ];
    }
}
