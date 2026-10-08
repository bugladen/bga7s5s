<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01084;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01084;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01084_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01084';
    }

    /**
     * Player 1 (Duelist actor) holds Master of Valroux Style; player 2's Foe is the adversary.
     *
     * @return array{0:_01084,1:Maneuver_01084,2:Character,3:Character}
     */
    private function duel(TestWorld $world, array $actorTraits = ['Duelist']): array
    {
        $risk = $world->placeCard(new _01084(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor', $actorTraits), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;

        /** @var Maneuver_01084 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function resolve(TestWorld $world, Maneuver_01084 $maneuver, Character $foe): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->adversaryId = $foe->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    /** Next round: the previous adversary (player 2) is now the actor, our Duelist is the adversary. */
    private function nextRound(TestWorld $world, Character $actor, Character $foe): void
    {
        $world->theah->duelActor = $foe;
        $world->theah->duelOpponent = $actor;
    }

    private function calcStats(TestWorld $world, Maneuver_01084 $maneuver): EventDuelCalculateCombatCardStats
    {
        $event = new EventDuelCalculateCombatCardStats();
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
        return $event;
    }

    public function tests(): array
    {
        return [
            'available when the round actor is a Duelist' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'Duelist');
            },

            'unavailable when the round actor is not a Duelist' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, []);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a Duelist');
            },

            // WHY: Maneuver::isAvailableToPlayer only blanks when the OWNER is a Character;
            // the owner here is a Risk, so blanking the actor must NOT disable this maneuver.
            'blanking the Duelist actor does not disable the maneuver (owner is a Risk)' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'Risk owner is not blanked');
            },

            'discount is 1 while the adversary is engaged' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $foe->Engaged = true;

                $explanations = [];
                Assert::same(1, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'discount');
                Assert::count(1, $explanations, 'explained');
            },

            'no discount when the adversary is not engaged' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);

                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'none');
                Assert::count(0, $explanations, 'no explanation');
            },

            // WHY: live getCharacterById + characterIsInDiscardOrLocker — a dead adversary
            // restores last-known Engaged, which must not grant the discount.
            'no discount when the engaged adversary is in discard or locker' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $foe->Engaged = true;
                $world->game->forceInDiscardOrLocker = true;

                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'dead adversary');
            },

            'no discount when queried for a different combat card' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $foe->Engaged = true;
                $other = $world->placeCard(new _01084(), Game::LOCATION_HAND, 1);

                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $other, $explanations), 'other card');
            },

            'resolve arms IncreaseAdversaryThrust and queues a card draw for the player' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                Assert::false($maneuver->IncreaseAdversaryThrust, 'starts disarmed');

                $this->resolve($world, $maneuver, $foe);

                Assert::true($maneuver->IncreaseAdversaryThrust, 'armed');
                Assert::true($risk->IsUpdated, 'persisted flag');
                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same(1, $draws[0]->playerId, 'player draws');
            },

            'resolve for a different maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'someOtherManeuver';
                $event->adversaryId = $foe->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::false($maneuver->IncreaseAdversaryThrust, 'not armed');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'calculate maneuver values adds 1 Riposte for this maneuver only' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $mine = new EventDuelCalculateManeuverValues();
                $mine->maneuverId = $maneuver->Id;
                $mine->riposte = 2;
                $mine->theah = $world->theah;
                $maneuver->handleEvent($mine);
                Assert::same(3, $mine->riposte, '+1 Riposte');
                Assert::count(1, $mine->explanations, 'explained');

                $other = new EventDuelCalculateManeuverValues();
                $other->maneuverId = 'someOtherManeuver';
                $other->riposte = 2;
                $other->theah = $world->theah;
                $maneuver->handleEvent($other);
                Assert::same(2, $other->riposte, 'other maneuver untouched');
            },

            'armed: adversary\'s next-round combat card gains +1 Thrust, then disarms' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);
                $this->resolve($world, $maneuver, $foe);
                $risk->IsUpdated = false;
                $this->nextRound($world, $actor, $foe);

                $event = $this->calcStats($world, $maneuver);

                Assert::same(1, $event->thrust, '+1 Thrust');
                Assert::false($maneuver->IncreaseAdversaryThrust, 'consumed');
                Assert::true($risk->IsUpdated, 'persisted');
                Assert::count(1, $event->explanations, 'explained');

                $second = $this->calcStats($world, $maneuver);
                Assert::same(0, $second->thrust, 'only once');
            },

            // WHY: "next round" — during OUR OWN round the adversary (player 2) is the opponent,
            // so the +1 must wait until the round where player 2 is the actor.
            'armed: no Thrust during the owner\'s own round and stays armed' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $this->resolve($world, $maneuver, $foe);

                $event = $this->calcStats($world, $maneuver);

                Assert::same(0, $event->thrust, 'not yet');
                Assert::true($maneuver->IncreaseAdversaryThrust, 'still armed');
            },

            'disarmed: next-round combat card stats are untouched' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->duel($world);
                $this->nextRound($world, $actor, $foe);

                $event = $this->calcStats($world, $maneuver);
                Assert::same(0, $event->thrust, 'no bonus');
            },

            'dashed Thrust is not increased but the arm is still consumed' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->duel($world);
                $this->resolve($world, $maneuver, $foe);
                $this->nextRound($world, $actor, $foe);

                $event = new EventDuelCalculateCombatCardStats();
                $event->theah = $world->theah;
                $event->dashedThrust = true;
                $maneuver->handleEvent($event);

                Assert::same(0, $event->thrust, 'dashed stays 0');
                Assert::false($maneuver->IncreaseAdversaryThrust, 'consumed');
            },

            'cancel clears the pending +1 Thrust' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $this->resolve($world, $maneuver, $foe);
                $risk->IsUpdated = false;

                $event = new EventManeuverCanceled();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::false($maneuver->IncreaseAdversaryThrust, 'cleared');
                Assert::true($risk->IsUpdated, 'persisted');
            },

            'cancel for another maneuver leaves the arm in place' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $this->resolve($world, $maneuver, $foe);

                $event = new EventManeuverCanceled();
                $event->maneuverId = 'someOtherManeuver';
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::true($maneuver->IncreaseAdversaryThrust, 'still armed');
            },

            'duel end clears an unused arm' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $this->resolve($world, $maneuver, $foe);
                $risk->IsUpdated = false;

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::false($maneuver->IncreaseAdversaryThrust, 'cleared');
                Assert::true($risk->IsUpdated, 'persisted');
            },

            // WHY (flagged, NOT fixed): the arm lives only on this maneuver instance. If the Risk is
            // moved out of the dueling line (e.g. Miyato/Ota 02043a -> Locker at EndOfRound) before
            // the adversary's next round, buildCity() no longer loads it and the +1 Thrust is lost.
            // Same gap as 01135; see journal 2026-10-06-03-maneuver-locker-gap-audit. This test only
            // pins the CURRENT instance-field design so a future global/EventHub fix is a conscious change.
            'arm is stored on the maneuver instance (known Locker-gap design)' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $this->resolve($world, $maneuver, $foe);

                $fresh = (new _01084())->getManeuvers()[0];
                Assert::true($maneuver->IncreaseAdversaryThrust, 'instance armed');
                Assert::false($fresh->IncreaseAdversaryThrust, 'a rebuilt maneuver has no memory of the arm');
            },
        ];
    }
}
