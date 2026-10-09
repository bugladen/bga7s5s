<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01107;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01107;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01107_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01107';
    }

    /** @return array{0:_01107,1:Maneuver_01107,2:GenericCharacter,3:GenericCharacter} */
    private function duel(TestWorld $world, int $priorWounds = 1): array
    {
        $risk = $world->placeCard(new _01107(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->theah->duelWoundsTaken[$actor->Id] = $priorWounds;

        /** @var Maneuver_01107 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function resolve(TestWorld $world, Maneuver_01107 $maneuver): void
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
            'available when the actor took wounds earlier in the duel' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 1);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'wounded earlier');
            },

            'unavailable when the actor has taken no prior wounds' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 0);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'unwounded');
            },

            'unavailable when the adversary is in discard or locker' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->game->forceInDiscardOrLocker = true;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'dead adversary');
            },

            'resolve wounds the adversary once' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);

                $this->resolve($world, $maneuver);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'one wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary');
                Assert::same(1, $wounds[0]->wounds, 'one');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $wounds[0]->abilityId, 'ability');
            },

            'resolve for another maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'someOtherManeuver';
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            // WHY: WillDieFromWound arms when Resolve remaining == 1; CharacterDestroyed then claims.
            'destroying a lethal-wound adversary claims the stored duel location' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);
                $foe->ModifiedResolve = 2;
                $foe->Wounds = 1;

                $this->resolve($world, $maneuver);
                $world->theah->takeQueuedEvents();

                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $foe->Id;
                $destroyed->theah = $world->theah;
                $maneuver->handleEvent($destroyed);

                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claims, 'claimed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'duel site');
                Assert::same($actor->ControllerId, $claims[0]->playerId, 'actor claims');
                Assert::same($actor->Id, $claims[0]->performerId, 'by actor');
            },

            'non-lethal wound does not claim on destroy' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $foe->ModifiedResolve = 3;
                $foe->Wounds = 0;

                $this->resolve($world, $maneuver);
                $world->theah->takeQueuedEvents();

                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $foe->Id;
                $destroyed->theah = $world->theah;
                $maneuver->handleEvent($destroyed);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'not lethal arm');
            },

            'does not claim when the location cannot be claimed' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $foe->ModifiedResolve = 1;
                $foe->Wounds = 0;
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;

                $this->resolve($world, $maneuver);
                $world->theah->takeQueuedEvents();

                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $foe->Id;
                $destroyed->theah = $world->theah;
                $maneuver->handleEvent($destroyed);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'blocked');
                Assert::count(1, $world->game->notify->messages, 'notified cannot claim');
            },

            'cancel clears the lethal-wound claim arm' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $foe->ModifiedResolve = 1;
                $foe->Wounds = 0;

                $this->resolve($world, $maneuver);
                $world->theah->takeQueuedEvents();

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);

                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $foe->Id;
                $destroyed->theah = $world->theah;
                $maneuver->handleEvent($destroyed);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'disarmed');
            },

            // WHY (journal 2026-10-06-03 locker-gap audit): EndOfRound clears while the Risk is
            // still in the duel line — NOT a post-locker deferred gap like 01084/01129.
            // Pin that EndOfRound disarms without needing the card still "in play" beyond line presence.
            'end of round clears the lethal-wound claim arm while still in line' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $foe->ModifiedResolve = 1;
                $foe->Wounds = 0;
                Assert::same(Game::LOCATION_HAND, $risk->Location, 'still in hand/line context');

                $this->resolve($world, $maneuver);
                $world->theah->takeQueuedEvents();

                $eor = new EventDuelEndOfRound();
                $eor->theah = $world->theah;
                $maneuver->handleEvent($eor);

                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $foe->Id;
                $destroyed->theah = $world->theah;
                $maneuver->handleEvent($destroyed);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'cleared at EoR');
            },
        ];
    }
}
