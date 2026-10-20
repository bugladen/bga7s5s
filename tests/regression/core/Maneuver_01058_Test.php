<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01058_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01058';
    }

    private function calc(TestWorld $world, Maneuver_01058 $maneuver, $actor, $foe, int $baseThrust = 0): EventDuelCalculateManeuverValues
    {
        $calc = new EventDuelCalculateManeuverValues();
        $calc->maneuverId = $maneuver->Id;
        $calc->actorId = $actor->Id;
        $calc->adversaryId = $foe->Id;
        $calc->thrust = $baseThrust;
        $calc->parry = 0;
        $calc->riposte = 0;
        $calc->explanations = [];
        $calc->theah = $world->theah;
        $maneuver->handleEvent($calc);
        return $calc;
    }

    public function tests(): array
    {
        return [
            'calc adds +1 Thrust and engages en garde adversary' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01058(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = false;
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01058 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $calc = $this->calc($world, $maneuver, $actor, $foe, 2);

                Assert::same(3, $calc->thrust, '+1 Thrust');
                $engage = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engage, 'engage queued');
                Assert::same($foe->Id, $engage[0]->cardId, 'adversary engaged');
                Assert::same($risk->Id, $engage[0]->sourceId, 'source risk');
                Assert::same($maneuver->Id, $engage[0]->abilityId, 'ability');
                Assert::same(1, $engage[0]->playerId, 'controller');
            },

            'calc adds +2 Thrust and does not engage when adversary already engaged' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01058(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01058 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $calc = $this->calc($world, $maneuver, $actor, $foe, 2);

                Assert::same(4, $calc->thrust, '+2 Thrust');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
            },

            'calc ignores other maneuver ids' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01058(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01058 $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = 'other';
                $calc->actorId = $actor->Id;
                $calc->adversaryId = $foe->Id;
                $calc->thrust = 0;
                $calc->explanations = [];
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);

                Assert::same(0, $calc->thrust, 'unchanged');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
