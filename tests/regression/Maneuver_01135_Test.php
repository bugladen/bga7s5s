<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01135;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01135;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01135_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01135';
    }

    /** @return array{0:_01135,1:Maneuver_01135,2:GenericCharacter,3:GenericCharacter} */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01135(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_01135 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    public function tests(): array
    {
        return [
            'available only while IN_DUEL' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'in duel');
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            // WHY (journal 2026-05-22): fires from ManeuverActivated (not Resolve) so the
            // choose-one state runs before cancel reactions that stack on Activated.
            'ManeuverActivated stacks transition 01135' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);

                $event = new EventManeuverActivated();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::true($maneuver->Used, 'setUsed on Activated');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01135', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($transitions[0], $world->theah->queuedEvents[0], 'stacked');
            },

            'choosing +2 Parry arms IsActive without wound or pending thrust' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01135,
                    'duelResolveManeuver_01135',
                    1
                );

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::same([], Maneuver_01135::getPendingThrustReductions($world->game), 'no pending');
                Assert::true($risk->IsUpdated, 'updated');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->parry, '+2 Parry');
                Assert::count(1, $calc->explanations, 'explained');
            },

            'choosing wound arms pending -2 Thrust against the adversary' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01135,
                    'duelResolveManeuver_01135',
                    2
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');

                $pending = Maneuver_01135::getPendingThrustReductions($world->game);
                Assert::count(1, $pending, 'armed');
                Assert::same($foe->Id, $pending[0]['adversaryId'], 'adversary id');
                Assert::same(2, $pending[0]['amount'], 'amount');
                Assert::same($maneuver->Id, $pending[0]['maneuverId'], 'maneuver');
                Assert::same($risk->Name, $pending[0]['sourceName'], 'name for column note');

                // WHY: +2 Parry branch only — ReduceThrustNextRound skips Parry on calc.
                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);
                Assert::same(0, $calc->parry, 'no Parry on wound choice');
            },

            // WHY: apply via static EventHub path so locker / clone-removed cases still hit
            // (Miyato/Ota); instance handleEvent deliberately has no CalculateCombatCardStats.
            'applyPendingThrustReductions reduces adversary combat-card Thrust and notes Maneuver column' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                Maneuver_01135::armPendingThrustReduction(
                    $world->game,
                    $foe->Id,
                    2,
                    $risk->getInjectCode(),
                    $maneuver->Id,
                    $risk->Name
                );
                $world->game->globals->set(Game::DUEL_ID, 7);
                $world->game->globals->set(Game::DUEL_ROUND, 3);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $foe->Id;
                $event->theah = $world->theah;
                $event->addThrust(5);
                Maneuver_01135::applyPendingThrustReductions($event);

                Assert::same(3, $event->thrust, '-2 Thrust');
                Assert::count(1, $event->explanations, 'explained');
                // Pending is not consumed until EndOfRound for that actor.
                Assert::count(1, Maneuver_01135::getPendingThrustReductions($world->game), 'still pending');
            },

            'applyPendingThrustReductions ignores other actors; dashed Thrust skips the numeric remove' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);
                Maneuver_01135::armPendingThrustReduction(
                    $world->game,
                    $foe->Id,
                    2,
                    $risk->getInjectCode(),
                    $maneuver->Id,
                    $risk->Name
                );

                $other = new EventDuelCalculateCombatCardStats();
                $other->actorId = $actor->Id;
                $other->theah = $world->theah;
                $other->addThrust(4);
                Maneuver_01135::applyPendingThrustReductions($other);
                Assert::same(4, $other->thrust, 'other actor untouched');

                // WHY: removeThrust is a no-op when dashedThrust (adds dashed explanation instead);
                // column note is also skipped. Pin current EventDuelCalculateCombatCardStats behavior.
                $dashed = new EventDuelCalculateCombatCardStats();
                $dashed->actorId = $foe->Id;
                $dashed->dashedThrust = true;
                $dashed->theah = $world->theah;
                // addThrust also no-ops when dashed — seed via reflection for remove path.
                $prop = new \ReflectionProperty(EventDuelCalculateCombatCardStats::class, 'thrust');
                $prop->setAccessible(true);
                $prop->setValue($dashed, 4);
                Maneuver_01135::applyPendingThrustReductions($dashed);
                Assert::same(4, $dashed->thrust, 'dashed: no numeric remove');
                Assert::true(count($dashed->explanations) >= 1, 'still explains attempt');
            },

            'expirePendingForActor drops entries for that adversary after their round' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                Maneuver_01135::armPendingThrustReduction($world->game, $foe->Id, 2, 'x', $maneuver->Id, 'n');
                Maneuver_01135::expirePendingForActor($world->game, $foe->Id);
                Assert::same([], Maneuver_01135::getPendingThrustReductions($world->game), 'expired');
            },

            'ManeuverCanceled clears pending for this maneuver id' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01135,
                    'x',
                    2
                );
                $world->theah->takeQueuedEvents();

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);

                Assert::same([], Maneuver_01135::getPendingThrustReductions($world->game), 'cleared');
                Assert::true($risk->IsUpdated, 'updated');
            },

            'DuelEnd clears all pending thrust reductions' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                Maneuver_01135::armPendingThrustReduction($world->game, $foe->Id, 2, 'x', $maneuver->Id, 'n');

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $maneuver->handleEvent($end);

                Assert::same([], Maneuver_01135::getPendingThrustReductions($world->game), 'cleared');
                Assert::true($risk->IsUpdated, 'updated');
            },

            'EndOfRound deactivates when it is the adversary\'s round ending' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);
                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01135,
                    'x',
                    1
                );
                $world->theah->takeQueuedEvents();
                // Adversary's turn: actor controller != owner → deactivate.
                $world->theah->duelActor = $foe;

                $end = new EventDuelEndOfRound();
                $end->theah = $world->theah;
                $maneuver->handleEvent($end);

                Assert::true($risk->IsUpdated, 'deactivated');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);
                Assert::same(0, $calc->parry, 'no longer active for Parry');
            },
        ];
    }
}
