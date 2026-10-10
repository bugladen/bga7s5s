<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01166;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01166;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01166_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01166';
    }

    /** @return array{0:_01166,1:Maneuver_01166,2:Character,3:Character} */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01166(), Game::LOCATION_DUELING_LINE, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01166 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function calc(TestWorld $world, Maneuver_01166 $maneuver, int $parry = 0): EventDuelCalculateManeuverValues
    {
        $calc = new EventDuelCalculateManeuverValues();
        $calc->maneuverId = $maneuver->Id;
        $calc->parry = $parry;
        $calc->explanations = [];
        $calc->theah = $world->theah;
        return $calc;
    }

    public function tests(): array
    {
        return [
            // WHY (journal 2026-03-31-08): unset($cards[$owner->Id]) — "each OTHER card".
            // Counting the maneuver card itself would give +1 Parry every resolve.
            'calc with only this card on the line adds 0 Parry' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $calc = $this->calc($world, $maneuver, 2);
                $maneuver->handleEvent($calc);

                Assert::same(2, $calc->parry, 'no other cards');
                Assert::count(1, $calc->explanations, 'explained');
            },

            'calc adds 1 Parry per other card on the controller\'s dueling line' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->placeCard(new _01166(), Game::LOCATION_DUELING_LINE, 1);
                $world->placeCard(new _01166(), Game::LOCATION_DUELING_LINE, 1);

                $calc = $this->calc($world, $maneuver, 0);
                $maneuver->handleEvent($calc);

                Assert::same(2, $calc->parry, 'two others');
                Assert::true(
                    str_contains($calc->explanations[0], '2'),
                    'explanation mentions count'
                );
            },

            'opponent dueling-line cards do not count' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->placeCard(new _01166(), Game::LOCATION_DUELING_LINE, 2);

                $calc = $this->calc($world, $maneuver, 0);
                $maneuver->handleEvent($calc);

                Assert::same(0, $calc->parry, 'foe line ignored');
            },

            'calc ignores a different maneuver id' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->placeCard(new _01166(), Game::LOCATION_DUELING_LINE, 1);

                $calc = $this->calc($world, $maneuver, 1);
                $calc->maneuverId = 'other';
                $maneuver->handleEvent($calc);

                Assert::same(1, $calc->parry, 'unchanged');
                Assert::same([], $calc->explanations, 'no explain');
            },
        ];
    }
}
