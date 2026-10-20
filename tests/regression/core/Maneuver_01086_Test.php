<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01086;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01086;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01086_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01086';
    }

    /** @return array{0:_01086,1:Maneuver_01086,2:Character,3:Character} */
    private function duel(TestWorld $world, array $foeTraits = []): array
    {
        $risk = $world->placeCard(new _01086(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe', $foeTraits), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;

        /** @var Maneuver_01086 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function resolve(TestWorld $world, Maneuver_01086 $maneuver, Character $foe, string $maneuverId = ''): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuverId !== '' ? $maneuverId : $maneuver->Id;
        $event->adversaryId = $foe->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'available during a duel (no extra gate)' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'discount is 1 while the adversary is a Mercenary' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world, ['Mercenary']);

                $explanations = [];
                Assert::same(1, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'discount');
                Assert::count(1, $explanations, 'explained');
            },

            'no discount against a non-Mercenary adversary' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);

                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'none');
                Assert::count(0, $explanations, 'no explanation');
            },

            // WHY: discount applies only to this card's own Maneuver cost, not other combat cards.
            'no discount when queried for a different combat card' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, ['Mercenary']);
                $other = $world->placeCard(new _01086(), Game::LOCATION_HAND, 1);

                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $other, $explanations), 'other card');
            },

            'resolve engages an unengaged adversary' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $foe->Engaged = false;

                $this->resolve($world, $maneuver, $foe);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($foe->Id, $engages[0]->cardId, 'adversary');
                Assert::same(1, $engages[0]->playerId, 'player');
                Assert::same($risk->Id, $engages[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $engages[0]->abilityId, 'ability');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },

            // WHY: "If they are already engaged, wound them instead" — exactly one of the two outcomes.
            'resolve wounds an already engaged adversary instead of engaging' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $foe->Engaged = true;

                $this->resolve($world, $maneuver, $foe);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $wounds[0]->abilityId, 'ability');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
            },

            'resolve for a different maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);

                $this->resolve($world, $maneuver, $foe, 'someOtherManeuver');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
