<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01052;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01052_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01052';
    }

    /** @return array{0:_01052,1:Maneuver_01052,2:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Character} */
    private function duel(TestWorld $world, int $wounds = 1): array
    {
        $risk = $world->placeCard(new _01052(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $actor->Wounds = $wounds;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;

        /** @var Maneuver_01052 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor];
    }

    private function resolve(TestWorld $world, Maneuver_01052 $maneuver): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    private function endOfRound(TestWorld $world, Maneuver_01052 $maneuver): void
    {
        $event = new EventDuelEndOfRound();
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            // WHY: card text has no wound/equip precondition for the Maneuver itself.
            'available regardless of wounds' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 0);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'resolve does not heal immediately' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $this->resolve($world, $maneuver);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'deferred to end of round');
            },

            'end of round heals wounded participant after resolve' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->duel($world);

                $this->resolve($world, $maneuver);
                $this->endOfRound($world, $maneuver);

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'heal');
                Assert::same($actor->Id, $heals[0]->characterId, 'participant');
                Assert::same(1, $heals[0]->wounds, 'one wound');
                Assert::same($risk->Id, $heals[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $heals[0]->abilityId, 'ability');
            },

            'end of round without resolve does not heal' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $this->endOfRound($world, $maneuver);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'not armed');
            },

            'end of round does not heal an unwounded participant' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 0);

                $this->resolve($world, $maneuver);
                $this->endOfRound($world, $maneuver);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'healthy');
            },

            // WHY: effect is "when your round ends" â€” one round only, flag cleared even when unwounded.
            'heal only happens once' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $this->resolve($world, $maneuver);
                $this->endOfRound($world, $maneuver);
                $world->theah->takeQueuedEvents();
                $this->endOfRound($world, $maneuver);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'second round');
            },

            'flag is cleared even when participant is unwounded at end of round' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world, 0);

                $this->resolve($world, $maneuver);
                $this->endOfRound($world, $maneuver);
                $actor->Wounds = 1;
                $this->endOfRound($world, $maneuver);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'flag consumed');
            },

            'end of round does not heal a participant in discard or locker' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->game->forceInDiscardOrLocker = true;

                $this->resolve($world, $maneuver);
                $this->endOfRound($world, $maneuver);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'dead participant');
            },

            'cancel clears the pending heal' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $this->resolve($world, $maneuver);
                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);
                $this->endOfRound($world, $maneuver);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'canceled');
            },

            'resolve for a different maneuver id does not arm' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'someOtherManeuver';
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);
                $this->endOfRound($world, $maneuver);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'not armed');
            },
        ];
    }
}