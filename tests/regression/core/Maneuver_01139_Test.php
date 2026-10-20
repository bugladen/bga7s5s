<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01139_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01139';
    }

    /** @return array{0:_01139,1:Maneuver_01139,2:Character,3:Character} */
    private function duel(TestWorld $world, int $baseCombat = 3): array
    {
        $risk = $world->placeCard(new _01139(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $actor->Combat = $baseCombat;
        $actor->ModifiedCombat = $baseCombat + 5;
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01139 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    public function tests(): array
    {
        return [
            // WHY: printed X = base Combat — must not use ModifiedCombat.
            'calc adds actor base Combat as Thrust and stamps goToLocker' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->duel($world, 3);

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->actorId = $actor->Id;
                $calc->thrust = 1;
                $calc->explanations = [];
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);

                Assert::same(4, $calc->thrust, 'base Combat 3 + prior 1');
                Assert::count(1, $calc->explanations, 'explained');
                Assert::true($risk->goToLocker, 'locker flag');
            },

            'calc with zero base Combat adds nothing but still stamps goToLocker' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->duel($world, 0);

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->actorId = $actor->Id;
                $calc->thrust = 2;
                $calc->explanations = [];
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);

                Assert::same(2, $calc->thrust, 'unchanged thrust total');
                Assert::true($risk->goToLocker, 'locker flag');
            },

            'calc ignores other maneuver ids' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->duel($world, 3);

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = 'other';
                $calc->actorId = $actor->Id;
                $calc->thrust = 0;
                $calc->explanations = [];
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);

                Assert::same(0, $calc->thrust, 'unchanged');
                Assert::false($risk->goToLocker, 'flag untouched');
            },
        ];
    }
}
