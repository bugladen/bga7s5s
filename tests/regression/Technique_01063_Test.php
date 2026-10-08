<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01063;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01063;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;

class Technique_01063_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01063';
    }

    /** @return array{0:_01063,1:GenericCharacter,2:Technique_01063} */
    private function duel(TestWorld $world): array
    {
        $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $bastien;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01063 $technique */
        $technique = $bastien->getTechniqueByClassId('Technique_01063');
        return [$bastien, $foe, $technique];
    }

    private function resolve(TestWorld $world, Technique_01063 $technique): void
    {
        $event = new EventResolveTechnique();
        $event->techniqueId = $technique->Id;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    private function endOfRound(TestWorld $world, Technique_01063 $technique, int $actorId): void
    {
        $event = new EventDuelEndOfRound();
        $event->actorId = $actorId;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    private function wounded(TestWorld $world, Technique_01063 $technique, int $characterId, int $wounds): void
    {
        $event = new EventCharacterWounded();
        $event->characterId = $characterId;
        $event->wounds = $wounds;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'available only in a duel' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);

                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'in duel');
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'outside duel (challenge is not a round)');
            },

            'unavailable when adversary is in discard or locker' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->game->forceInDiscardOrLocker = true;

                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'dead adversary');
            },

            'unavailable when Bastien is blanked' => function () {
                $world = new TestWorld();
                [$bastien, , $technique] = $this->duel($world);
                $bastien->addCondition(Game::FATES_SILENCE_CONDITION);

                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            // WHY: "if Bastien was not wounded during it" - unwounded round at end-of-round wounds the adversary.
            'end of round wounds adversary when Bastien was not wounded' => function () {
                $world = new TestWorld();
                [$bastien, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique);
                $this->endOfRound($world, $technique, $bastien->Id);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound queued');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary wounded');
                Assert::same($bastien->Id, $wounds[0]->sourceId, 'source Bastien');
                Assert::same(1, $wounds[0]->wounds, '1 wound');
            },

            'end of round does not wound adversary when Bastien was wounded this round' => function () {
                $world = new TestWorld();
                [$bastien, , $technique] = $this->duel($world);
                $this->resolve($world, $technique);
                $this->wounded($world, $technique, $bastien->Id, 1);
                $this->endOfRound($world, $technique, $bastien->Id);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },

            'wound to someone else, zero wounds, or outside duel does not count against Bastien' => function () {
                $world = new TestWorld();
                [$bastien, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique);

                $this->wounded($world, $technique, $foe->Id, 1);
                $this->wounded($world, $technique, $bastien->Id, 0);
                $world->game->globals->set(Game::IN_DUEL, false);
                $this->wounded($world, $technique, $bastien->Id, 1);
                $world->game->globals->set(Game::IN_DUEL, true);

                $this->endOfRound($world, $technique, $bastien->Id);
                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'still wounds adversary');
            },

            'wounds before activation are ignored' => function () {
                $world = new TestWorld();
                [$bastien, , $technique] = $this->duel($world);
                $this->wounded($world, $technique, $bastien->Id, 1);
                $this->resolve($world, $technique);
                $this->endOfRound($world, $technique, $bastien->Id);

                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'earlier wound irrelevant');
            },

            'end of round without activation does nothing' => function () {
                $world = new TestWorld();
                [$bastien, , $technique] = $this->duel($world);
                $this->endOfRound($world, $technique, $bastien->Id);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'inactive');
            },

            'does not wound on the adversary round end' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique);
                $this->endOfRound($world, $technique, $foe->Id);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'not Bastien round');
            },

            'fires once then deactivates' => function () {
                $world = new TestWorld();
                [$bastien, , $technique] = $this->duel($world);
                $this->resolve($world, $technique);
                $this->endOfRound($world, $technique, $bastien->Id);
                $this->endOfRound($world, $technique, $bastien->Id);

                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'one wound only');
            },

            'canceled technique does not wound' => function () {
                $world = new TestWorld();
                [$bastien, , $technique] = $this->duel($world);
                $this->resolve($world, $technique);

                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = $technique->Id;
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);

                $this->endOfRound($world, $technique, $bastien->Id);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'canceled');
            },
        ];
    }
}
