<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01088;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01088;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01088_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01088';
    }

    /** @return array{0:_01088,1:Maneuver_01088} */
    private function duel(TestWorld $world, array $foeTraits = ['Mercenary']): array
    {
        $risk = $world->placeCard(new _01088(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe', $foeTraits), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;

        /** @var Maneuver_01088 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver];
    }

    public function tests(): array
    {
        return [
            'available when the adversary is a Mercenary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'Mercenary adversary');
            },

            'unavailable when the adversary is not a Mercenary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, []);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a Mercenary');
            },

            // WHY: the log wording says "increases the Adversary's Riposte" but the printed effect and the code
            // both raise the maneuver user's OWN Riposte ($event->riposte is the maneuver's Riposte value).
            // Pin the numeric behavior, not the misleading wording.
            'calculate maneuver values adds 1 to the maneuver Riposte for this maneuver only' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $mine = new EventDuelCalculateManeuverValues();
                $mine->maneuverId = $maneuver->Id;
                $mine->riposte = 2;
                $mine->parry = 1;
                $mine->thrust = 3;
                $mine->theah = $world->theah;
                $maneuver->handleEvent($mine);

                Assert::same(3, $mine->riposte, '+1 Riposte');
                Assert::same(1, $mine->parry, 'Parry untouched');
                Assert::same(3, $mine->thrust, 'Thrust untouched');
                Assert::count(1, $mine->explanations, 'explained');

                $other = new EventDuelCalculateManeuverValues();
                $other->maneuverId = 'someOtherManeuver';
                $other->riposte = 2;
                $other->theah = $world->theah;
                $maneuver->handleEvent($other);
                Assert::same(2, $other->riposte, 'other maneuver untouched');
            },
        ];
    }
}
