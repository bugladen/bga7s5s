<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01204;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01204;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;

class Technique_01204_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01204';
    }

    /**
     * Host wears Syrneth Hand; duel is live.
     *
     * @return array{0:_01204,1:GenericCharacter,2:GenericCharacter,3:Technique_01204}
     */
    private function duel(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var _01204 $hand */
        $hand = $world->placeCard(new _01204(), Game::LOCATION_CITY_DOCKS, 1);
        $hand->AttachedToId = $host->Id;
        $host->Attachments[] = $hand->Id;
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $host;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01204 $technique */
        $technique = $hand->getTechniques()[0];
        return [$hand, $host, $foe, $technique];
    }

    private function resolve(TestWorld $world, Technique_01204 $technique, int $adversaryId): void
    {
        $event = new EventResolveTechnique();
        $event->techniqueId = $technique->Id;
        $event->adversaryId = $adversaryId;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    private function combatStats(TestWorld $world, Technique_01204 $technique, int $actorId, int $parry = 3): EventDuelCalculateCombatCardStats
    {
        $event = new EventDuelCalculateCombatCardStats();
        $event->actorId = $actorId;
        $event->theah = $world->theah;
        if ($parry > 0) {
            $event->addParry($parry);
        }
        $technique->handleEvent($event);
        return $event;
    }

    public function tests(): array
    {
        return [
            'starts with deferred state cleared' => function () {
                $technique = new Technique_01204();
                Assert::false($technique->ReduceAdversaryParry, 'flag');
                Assert::same(0, $technique->AdversaryId, 'adversary');
            },

            // WHY (journal 2026-09-19-04): Resolve wounds performer and arms -2 Parry with AdversaryId.
            'resolve wounds the host and arms ReduceAdversaryParry with AdversaryId' => function () {
                $world = new TestWorld();
                [$hand, $host, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                Assert::true($technique->ReduceAdversaryParry, 'armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'adversary');
                Assert::true($hand->IsUpdated, 'persisted');
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wound, 'wound');
                Assert::same($host->Id, $wound[0]->characterId, 'host');
                Assert::same(1, $wound[0]->wounds, '1 wound');
                Assert::same($hand->Id, $wound[0]->sourceId, 'source');
            },

            'resolve falls back to CHOSEN_TARGET when event has no adversary' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $this->resolve($world, $technique, 0);

                Assert::true($technique->ReduceAdversaryParry, 'armed');
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

                Assert::false($technique->ReduceAdversaryParry, 'not armed');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            // WHY: apply when actorId == stored AdversaryId, then clear (journal 2026-09-19-04).
            'adversary combat card loses 2 Parry and clears the arm' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $world->theah->takeQueuedEvents();
                $world->game->globals->set(Game::DUEL_ID, 1);
                $world->game->globals->set(Game::DUEL_ROUND, 2);

                $event = $this->combatStats($world, $technique, $foe->Id, 3);

                Assert::same(1, $event->parry, '3 - 2');
                Assert::count(1, $event->explanations, 'explained');
                Assert::false($technique->ReduceAdversaryParry, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
                $notes = array_filter($world->game->notify->messages, fn($m) => $m['type'] === 'duelRoundColumnNote');
                Assert::count(1, $notes, 'technique column note');
            },

            // WHY: challenger round-1 combat card must NOT consume the arm.
            'owner combat card does not apply or clear the arm' => function () {
                $world = new TestWorld();
                [, $host, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $world->theah->takeQueuedEvents();

                $event = $this->combatStats($world, $technique, $host->Id, 3);

                Assert::same(3, $event->parry, 'unchanged');
                Assert::true($technique->ReduceAdversaryParry, 'still armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'kept');
            },

            'dashed Parry skips removeParry but still clears' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $world->theah->takeQueuedEvents();

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $foe->Id;
                $event->theah = $world->theah;
                $event->addParry(3);
                $event->dashedParry = true;
                $technique->handleEvent($event);

                Assert::same(3, $event->parry, 'dashed unchanged');
                Assert::false($technique->ReduceAdversaryParry, 'cleared after apply attempt');
            },

            // WHY (journal 2026-09-07 Dame copy): Character-owned clone applies without Attachment.
            'Character-owned copy still applies -2 Parry' => function () {
                $world = new TestWorld();
                [, $host, $foe, $technique] = $this->duel($world);
                $technique->OwnerId = $host->Id;
                $this->resolve($world, $technique, $foe->Id);
                $world->theah->takeQueuedEvents();

                $event = $this->combatStats($world, $technique, $foe->Id, 4);

                Assert::same(2, $event->parry, '4 - 2');
                Assert::false($technique->ReduceAdversaryParry, 'cleared');
            },

            'unattached Attachment owner does not apply' => function () {
                $world = new TestWorld();
                [$hand, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $world->theah->takeQueuedEvents();
                $hand->AttachedToId = 0;

                $event = $this->combatStats($world, $technique, $foe->Id, 3);

                Assert::same(3, $event->parry, 'blocked');
                Assert::true($technique->ReduceAdversaryParry, 'still armed');
            },

            // WHY (journal 2026-04-09-06): cancel must clear deferred flag after resolve.
            'canceled technique clears deferred state' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = $technique->Id;
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);

                Assert::false($technique->ReduceAdversaryParry, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
                $event = $this->combatStats($world, $technique, $foe->Id, 3);
                Assert::same(3, $event->parry, 'no effect');
            },

            // WHY (journal 2026-09-19-04): Resolve before Accept/Reject — refuse must not leak.
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

                Assert::false($technique->ReduceAdversaryParry, 'cleared');
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

                Assert::true($technique->ReduceAdversaryParry, 'still armed');
            },

            'duel end clears deferred state' => function () {
                $world = new TestWorld();
                [, , $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $technique->handleEvent($end);

                Assert::false($technique->ReduceAdversaryParry, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
            },
        ];
    }
}
