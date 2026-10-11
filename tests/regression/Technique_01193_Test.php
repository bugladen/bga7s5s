<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01193;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01193;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;

class Technique_01193_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01193';
    }

    /**
     * Host wears Burnished Cuirass; duel is live.
     *
     * @return array{0:_01193,1:GenericCharacter,2:GenericCharacter,3:Technique_01193}
     */
    private function duel(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var _01193 $cuirass */
        $cuirass = $world->placeCard(new _01193(), Game::LOCATION_CITY_DOCKS, 1);
        $cuirass->AttachedToId = $host->Id;
        $host->Attachments[] = $cuirass->Id;
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $host;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01193 $technique */
        $technique = $cuirass->getTechniques()[0];
        return [$cuirass, $host, $foe, $technique];
    }

    private function resolve(TestWorld $world, Technique_01193 $technique, int $adversaryId): void
    {
        $event = new EventResolveTechnique();
        $event->techniqueId = $technique->Id;
        $event->adversaryId = $adversaryId;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    private function combatStats(TestWorld $world, Technique_01193 $technique, int $actorId, int $thrust = 3): EventDuelCalculateCombatCardStats
    {
        $event = new EventDuelCalculateCombatCardStats();
        $event->actorId = $actorId;
        $event->theah = $world->theah;
        if ($thrust > 0) {
            $event->addThrust($thrust);
        }
        $technique->handleEvent($event);
        return $event;
    }

    public function tests(): array
    {
        return [
            'starts with deferred state cleared' => function () {
                $technique = new Technique_01193();
                Assert::false($technique->ReduceAdversaryThrust, 'flag');
                Assert::same(0, $technique->AdversaryId, 'adversary');
            },

            // WHY (journal 2026-09-18-02): arm stores AdversaryId from Resolve (challenge-safe).
            'resolve arms ReduceAdversaryThrust and stores AdversaryId' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                Assert::true($technique->ReduceAdversaryThrust, 'armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'adversary');
            },

            // WHY (journal 2026-09-18): challenge Resolve may omit adversaryId; fall back to CHOSEN_TARGET.
            'resolve falls back to CHOSEN_TARGET when event has no adversary' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $this->resolve($world, $technique, 0);

                Assert::true($technique->ReduceAdversaryThrust, 'armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'CHOSEN_TARGET');
            },

            'resolve for another technique does not arm' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $event = new EventResolveTechnique();
                $event->techniqueId = 'someOtherTechnique';
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->ReduceAdversaryThrust, 'not armed');
            },

            // WHY: apply when actorId == stored AdversaryId (adversary's combat card), then clear.
            'adversary combat card loses 1 Thrust and clears the arm' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $event = $this->combatStats($world, $technique, $foe->Id, 3);

                Assert::same(2, $event->thrust, '-1 Thrust');
                Assert::count(1, $event->explanations, 'explained');
                Assert::false($technique->ReduceAdversaryThrust, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
            },

            // WHY (journal 2026-09-18): challenger round-1 combat card must NOT consume the arm.
            'owner combat card does not apply or clear the arm' => function () {
                $world = new TestWorld();
                [, $host, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $event = $this->combatStats($world, $technique, $host->Id, 3);

                Assert::same(3, $event->thrust, 'unchanged');
                Assert::true($technique->ReduceAdversaryThrust, 'still armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'kept');
            },

            'dashed Thrust skips removeThrust but still clears' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $foe->Id;
                $event->theah = $world->theah;
                // Seed thrust before dashing so removeThrust is the path under test.
                $event->addThrust(3);
                $event->dashedThrust = true;
                $technique->handleEvent($event);

                Assert::same(3, $event->thrust, 'dashed unchanged');
                Assert::false($technique->ReduceAdversaryThrust, 'cleared after apply attempt');
            },

            // WHY (journal 2026-09-07 Dame copy): Character-owned clone applies without Attachment.
            'Character-owned copy still applies -1 Thrust' => function () {
                $world = new TestWorld();
                [, $host, $foe, $technique] = $this->duel($world);
                $technique->OwnerId = $host->Id;
                $this->resolve($world, $technique, $foe->Id);

                $event = $this->combatStats($world, $technique, $foe->Id, 4);

                Assert::same(3, $event->thrust, '-1 Thrust');
                Assert::false($technique->ReduceAdversaryThrust, 'cleared');
            },

            'unattached Attachment owner does not apply' => function () {
                $world = new TestWorld();
                [$cuirass, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $cuirass->AttachedToId = 0;

                $event = $this->combatStats($world, $technique, $foe->Id, 3);

                Assert::same(3, $event->thrust, 'blocked');
                Assert::true($technique->ReduceAdversaryThrust, 'still armed');
            },

            'canceled technique clears deferred state' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = $technique->Id;
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);

                Assert::false($technique->ReduceAdversaryThrust, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
                $event = $this->combatStats($world, $technique, $foe->Id, 3);
                Assert::same(3, $event->thrust, 'no effect');
            },

            // WHY (journal 2026-09-18): Resolve before Accept/Reject — refuse must not leak into later duel.
            'challenge rejected by this challenger clears the arm' => function () {
                $world = new TestWorld();
                [, $host, $foe, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $this->resolve($world, $technique, $foe->Id);

                $event = new EventChallengeRejected();
                $event->challengerId = $host->Id;
                $event->targetId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->ReduceAdversaryThrust, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
            },

            'someone else\'s rejected challenge does not clear' => function () {
                $world = new TestWorld();
                [, $host, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $event = new EventChallengeRejected();
                $event->challengerId = $foe->Id;
                $event->targetId = $host->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($technique->ReduceAdversaryThrust, 'still armed');
            },

            'duel end clears deferred state' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $technique->handleEvent($end);

                Assert::false($technique->ReduceAdversaryThrust, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
            },
        ];
    }
}
