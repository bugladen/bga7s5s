<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01136;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01136;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01136_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01136';
    }

    /** @return array{0:_01136,1:Maneuver_01136,2:Character,3:Character} */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01136(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01136 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function calc(TestWorld $world, Maneuver_01136 $maneuver, Character $actor, int $riposte = 0): EventDuelCalculateManeuverValues
    {
        $calc = new EventDuelCalculateManeuverValues();
        $calc->maneuverId = $maneuver->Id;
        $calc->actorId = $actor->Id;
        $calc->riposte = $riposte;
        $calc->thrust = 0;
        $calc->parry = 0;
        $calc->explanations = [];
        $calc->theah = $world->theah;
        $maneuver->handleEvent($calc);
        return $calc;
    }

    public function tests(): array
    {
        return [
            'available when the duel actor is the only controlled character at the location' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'unavailable when another controlled character shares the actor location' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->placeCharacter(new GenericCharacter('Buddy'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not alone');
            },

            'an enemy at the location does not block availability' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                // foe already at Docks from duel()
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'foe ignored');
            },

            'calc adds +1 Riposte for this maneuver id' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->duel($world);

                $calc = $this->calc($world, $maneuver, $actor, 2);

                Assert::same(3, $calc->riposte, '+1 Riposte');
                Assert::count(1, $calc->explanations, 'explained');
                Assert::true(str_contains($calc->explanations[0], $risk->getInjectCode()) || $calc->explanations[0] !== '', 'non-empty');
            },

            'calc ignores other maneuver ids' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = 'other';
                $calc->actorId = $actor->Id;
                $calc->riposte = 1;
                $calc->explanations = [];
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);

                Assert::same(1, $calc->riposte, 'unchanged');
            },
        ];
    }
}
