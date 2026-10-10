<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01131_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01131';
    }

    /** @return array{0:_01131,1:Maneuver_01131,2:GenericCharacter,3:GenericCharacter} */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01131(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01131 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function resolve(TestWorld $world, Maneuver_01131 $maneuver): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'available when either participant has an attachment' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->Attachments[] = 1;
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'actor equipped');
            },

            'available when only the adversary has an attachment' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $foe->Attachments[] = 2;
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'foe equipped');
            },

            'unavailable when neither participant has an attachment' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'none');
            },

            'resolve wounds only participants that have attachments' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);
                $actor->Attachments[] = 1;

                $this->resolve($world, $maneuver);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'only actor');
                Assert::same($actor->Id, $wounds[0]->characterId, 'actor wounded');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same($maneuver->Id, $wounds[0]->abilityId, 'ability');
                Assert::false(in_array($foe->Id, array_map(fn($w) => $w->characterId, $wounds), true), 'foe spared');
            },

            'resolve wounds both when both have attachments' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->duel($world);
                $actor->Attachments[] = 1;
                $foe->Attachments[] = 2;

                $this->resolve($world, $maneuver);

                $ids = array_map(
                    fn($w) => $w->characterId,
                    $world->theah->queuedOfType(EventCharacterBeingWounded::class)
                );
                Assert::same([$actor->Id, $foe->Id], $ids, 'both');
            },

            'resolve does nothing when the maneuver id does not match' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->Attachments[] = 1;

                $event = new EventResolveManeuver();
                $event->maneuverId = 'other';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
