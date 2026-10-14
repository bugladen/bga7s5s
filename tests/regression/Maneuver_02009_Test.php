<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02009;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_02009_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02009';
    }

    /**
     * Murder in hand; duel with actor having optional traits.
     * Keyed by ClassId (Maneuver_02009_Thug / _Duelist / _Spy) — Id is owner-prefixed after place.
     *
     * @return array{0:_02009,1:array<string,Maneuver_02009>,2:GenericCharacter,3:GenericCharacter}
     */
    private function duel(TestWorld $world, array $actorTraits = []): array
    {
        $risk = $world->placeCard(new _02009(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(
            new GenericCharacter('Actor', $actorTraits),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);

        /** @var array<string, Maneuver_02009> $byClass */
        $byClass = [];
        foreach ($risk->getManeuvers() as $maneuver) {
            $byClass[$maneuver->ClassId] = $maneuver;
        }
        return [$risk, $byClass, $actor, $foe];
    }

    public function tests(): array
    {
        return [
            'construct ClassIds are Thug / Duelist / Spy' => function () {
                $thug = new Maneuver_02009('Thug', 't');
                $thug->setId('Maneuver_02009_Thug');
                $duelist = new Maneuver_02009('Duelist', 'd');
                $duelist->setId('Maneuver_02009_Duelist');
                $spy = new Maneuver_02009('Spy', 's');
                $spy->setId('Maneuver_02009_Spy');

                Assert::same('Maneuver_02009_Thug', $thug->ClassId, 'thug');
                Assert::same('Maneuver_02009_Duelist', $duelist->ClassId, 'duelist');
                Assert::same('Maneuver_02009_Spy', $spy->ClassId, 'spy');
            },

            // WHY: availability gated by performer trait matching each maneuver instance.
            'Thug maneuver available only when actor is a Thug' => function () {
                $world = new TestWorld();
                [, $byClass] = $this->duel($world, ['Thug']);
                Assert::true($byClass['Maneuver_02009_Thug']->isAvailableToPlayer(1, $world->theah), 'thug ok');
                Assert::false($byClass['Maneuver_02009_Duelist']->isAvailableToPlayer(1, $world->theah), 'duelist no');
                Assert::false($byClass['Maneuver_02009_Spy']->isAvailableToPlayer(1, $world->theah), 'spy no');
            },

            'Duelist maneuver available only when actor is a Duelist' => function () {
                $world = new TestWorld();
                [, $byClass] = $this->duel($world, ['Duelist']);
                Assert::false($byClass['Maneuver_02009_Thug']->isAvailableToPlayer(1, $world->theah), 'thug no');
                Assert::true($byClass['Maneuver_02009_Duelist']->isAvailableToPlayer(1, $world->theah), 'duelist ok');
                Assert::false($byClass['Maneuver_02009_Spy']->isAvailableToPlayer(1, $world->theah), 'spy no');
            },

            'Spy maneuver available only when actor is a Spy' => function () {
                $world = new TestWorld();
                [, $byClass] = $this->duel($world, ['Spy']);
                Assert::false($byClass['Maneuver_02009_Thug']->isAvailableToPlayer(1, $world->theah), 'thug no');
                Assert::false($byClass['Maneuver_02009_Duelist']->isAvailableToPlayer(1, $world->theah), 'duelist no');
                Assert::true($byClass['Maneuver_02009_Spy']->isAvailableToPlayer(1, $world->theah), 'spy ok');
            },

            'all unavailable when actor has none of the traits' => function () {
                $world = new TestWorld();
                [, $byClass] = $this->duel($world, []);
                foreach ($byClass as $maneuver) {
                    Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), $maneuver->ClassId);
                }
            },

            'actor with all three traits unlocks all three maneuvers' => function () {
                $world = new TestWorld();
                [, $byClass] = $this->duel($world, ['Thug', 'Duelist', 'Spy']);
                foreach ($byClass as $maneuver) {
                    Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), $maneuver->ClassId);
                }
            },

            // WHY: resolve wounds the adversary (same effect for each trait variant).
            'resolve wounds the adversary for Thug maneuver' => function () {
                $world = new TestWorld();
                [$risk, $byClass, , $foe] = $this->duel($world, ['Thug']);
                $maneuver = $byClass['Maneuver_02009_Thug'];

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary');
                Assert::same(1, $wounds[0]->wounds, 'one');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $wounds[0]->abilityId, 'ability');
            },

            'resolve wounds the adversary for Duelist and Spy maneuvers' => function () {
                foreach (['Maneuver_02009_Duelist' => ['Duelist'], 'Maneuver_02009_Spy' => ['Spy']] as $classId => $traits) {
                    $world = new TestWorld();
                    [, $byClass, , $foe] = $this->duel($world, $traits);
                    $maneuver = $byClass[$classId];

                    $event = new EventResolveManeuver();
                    $event->maneuverId = $maneuver->Id;
                    $event->playerId = 1;
                    $event->theah = $world->theah;
                    $maneuver->handleEvent($event);

                    $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                    Assert::count(1, $wounds, $classId . ' wound');
                    Assert::same($foe->Id, $wounds[0]->characterId, $classId . ' foe');
                }
            },

            'resolve ignores other maneuver ids' => function () {
                $world = new TestWorld();
                [, $byClass] = $this->duel($world, ['Thug']);

                $event = new EventResolveManeuver();
                $event->maneuverId = $byClass['Maneuver_02009_Spy']->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $byClass['Maneuver_02009_Thug']->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'ignored');
            },
        ];
    }
}
