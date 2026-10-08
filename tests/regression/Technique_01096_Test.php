<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01096;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01096;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_01096_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01096';
    }

    /**
     * Raton and the adversary share Docks, duel is live, adversary wears a legal Flintlock.
     *
     * @return array{0:_01096,1:GenericCharacter,2:Technique_01096,3:_01049}
     */
    private function duel(TestWorld $world): array
    {
        $raton = $world->placeCharacter(new _01096(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $flint = $this->equip($world, $foe, new _01049());
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $raton;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01096 $technique */
        $technique = $raton->getTechniques()[0];
        return [$raton, $foe, $technique, $flint];
    }

    /**
     * Challenge step: no duel row yet, so the only adversary source is CHOSEN_TARGET.
     *
     * @return array{0:_01096,1:GenericCharacter,2:Technique_01096,3:_01049}
     */
    private function challenge(TestWorld $world): array
    {
        [$raton, $foe, $technique, $flint] = $this->duel($world);
        $world->game->globals->set(Game::IN_DUEL, false);
        $world->theah->duelActor = null;
        $world->theah->duelOpponent = null;
        $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
        return [$raton, $foe, $technique, $flint];
    }

    private function equip(TestWorld $world, Character $host, Attachment $attachment): Attachment
    {
        $placed = $world->placeCard($attachment, $host->Location, $host->ControllerId);
        $placed->AttachedToId = $host->Id;
        $host->Attachments[] = $placed->Id;
        return $placed;
    }

    private function resolve(TestWorld $world, Technique_01096 $technique, int $adversaryId): void
    {
        $event = new EventResolveTechnique();
        $event->techniqueId = $technique->Id;
        $event->adversaryId = $adversaryId;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    private function endOfRound(TestWorld $world, Technique_01096 $technique, int $actorId): void
    {
        $event = new EventDuelEndOfRound();
        $event->actorId = $actorId;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    private function wounded(TestWorld $world, Technique_01096 $technique, int $characterId, int $wounds): void
    {
        $event = new EventCharacterWounded();
        $event->characterId = $characterId;
        $event->wounds = $wounds;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    private function steals(TestWorld $world): int
    {
        return count($world->theah->queuedOfType(EventTransition::class));
    }

    private function actThrows(TestWorld $world, Technique_01096 $technique, int $id): bool
    {
        try {
            $technique->actFromTechniqueWithId($world->game, States::DUEL_END_OF_ROUND_01096, 'duelEndOfRound_01096', $id);
        } catch (\BgaUserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            // ---- availability ----
            'available in a duel when the adversary has a legal attachment' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'legal attachment');
            },

            'unavailable when the adversary has no attachment' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $foe->Attachments = [];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'nothing to steal');
            },

            // WHY (journal 2026-09-19): Cavalier Hat is Duelist-only; Raton is not a Duelist, so it is not a legal steal.
            'unavailable when the only attachment cannot equip to Raton' => function () {
                $world = new TestWorld();
                [, $foe, $technique, $flint] = $this->duel($world);
                $foe->Attachments = [];
                $this->equip($world, $foe, new _01073());
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'illegal only');
            },

            'available when one attachment is illegal but another is legal' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->equip($world, $foe, new _01073());
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'mixed attachments');
            },

            'unavailable when Fate\'s Silence blanks Raton' => function () {
                $world = new TestWorld();
                [$raton, , $technique] = $this->duel($world);
                $raton->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'unavailable with no duel adversary and no challenge target' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->theah->duelOpponent = null;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no adversary');
            },

            // WHY (journal 2026-09-18): challenge has no duel row; adversary comes from CHOSEN_TARGET, not getDuelRoundOpponent.
            'available during a challenge using CHOSEN_TARGET (no duel opponent)' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->challenge($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'challenge target has an attachment');
            },

            'unavailable during a challenge when CHOSEN_TARGET is unset' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->challenge($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, 0);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no target');
            },

            'unavailable during a challenge when the target has no legal attachment' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->challenge($world);
                $foe->Attachments = [];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'nothing to steal');
            },

            // ---- arming ----
            'resolve arms the technique and stores the adversary' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                Assert::true($technique->IsActive, 'armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'adversary stored');
            },

            // WHY (journal 2026-09-18): challenge Resolve carries no adversaryId; fall back to CHOSEN_TARGET.
            'resolve falls back to CHOSEN_TARGET when the event has no adversary' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->challenge($world);
                $this->resolve($world, $technique, 0);
                Assert::true($technique->IsActive, 'armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'CHOSEN_TARGET used');
            },

            'resolve for another technique does not arm' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $event = new EventResolveTechnique();
                $event->techniqueId = 'someOtherTechnique';
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);
                Assert::false($technique->IsActive, 'not armed');
                Assert::same(0, $technique->AdversaryId, 'no adversary');
            },

            // ---- end of the adversary's round ----
            'adversary round ends unwounded: steal transition queued and state cleared' => function () {
                $world = new TestWorld();
                [$raton, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $this->endOfRound($world, $technique, $foe->Id);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'steal offered');
                Assert::same('01096', $transitions[0]->transition, 'transition name');
                Assert::same($raton->Id, $transitions[0]->sourceId, 'source Raton');
                Assert::same($technique->Id, $transitions[0]->internalId, 'internal id');
                Assert::false($technique->IsActive, 'disarmed after firing');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
            },

            'adversary wounded during their round: no steal, but state still clears' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $this->wounded($world, $technique, $foe->Id, 1);
                $this->endOfRound($world, $technique, $foe->Id);

                Assert::same(0, $this->steals($world), 'wounded adversary keeps attachments');
                Assert::false($technique->IsActive, 'disarmed');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
            },

            // WHY (journal 2026-09-18): only wounds dealt while IN_DUEL count as "during" the adversary's round.
            'adversary wound outside a duel (challenge threat) does not block the steal' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $world->game->globals->set(Game::IN_DUEL, false);
                $this->wounded($world, $technique, $foe->Id, 1);
                $world->game->globals->set(Game::IN_DUEL, true);
                $this->endOfRound($world, $technique, $foe->Id);

                Assert::same(1, $this->steals($world), 'pre-duel wound ignored');
            },

            'zero-wound event or a wound to someone else does not block the steal' => function () {
                $world = new TestWorld();
                [$raton, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $this->wounded($world, $technique, $foe->Id, 0);
                $this->wounded($world, $technique, $raton->Id, 2);
                $this->endOfRound($world, $technique, $foe->Id);

                Assert::same(1, $this->steals($world), 'only an adversary wound counts');
            },

            'wounds before the technique is armed are ignored' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->wounded($world, $technique, $foe->Id, 1);
                $this->resolve($world, $technique, $foe->Id);
                $this->endOfRound($world, $technique, $foe->Id);

                Assert::same(1, $this->steals($world), 'earlier wound irrelevant');
            },

            // WHY (journal 2026-09-18): challenger round 1 ends before the adversary's first round; it must not consume the arm.
            'a non-adversary end of round keeps the technique armed' => function () {
                $world = new TestWorld();
                [$raton, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $this->endOfRound($world, $technique, $raton->Id);

                Assert::true($technique->IsActive, 'still armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'adversary kept');
                Assert::same(0, $this->steals($world), 'nothing yet');

                $this->endOfRound($world, $technique, $foe->Id);
                Assert::same(1, $this->steals($world), 'steals at the adversary round');
            },

            // WHY (journal 2026-09-18): wounds taken in Raton's own round must not carry into the adversary's round.
            'wound recorded before a non-adversary round end is reset by it' => function () {
                $world = new TestWorld();
                [$raton, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $this->wounded($world, $technique, $foe->Id, 1);
                $this->endOfRound($world, $technique, $raton->Id);
                $this->endOfRound($world, $technique, $foe->Id);

                Assert::same(1, $this->steals($world), 'stale wound flag cleared');
            },

            'end of round without arming does nothing' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->endOfRound($world, $technique, $foe->Id);
                Assert::count(0, $world->theah->queuedEvents, 'inactive');
            },

            'fires once: a second adversary round does not steal again' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $this->endOfRound($world, $technique, $foe->Id);
                $this->endOfRound($world, $technique, $foe->Id);
                Assert::same(1, $this->steals($world), 'one steal only');
            },

            'adversary lost every legal attachment by round end: no steal, state clears' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $foe->Attachments = [];
                $this->endOfRound($world, $technique, $foe->Id);

                Assert::same(0, $this->steals($world), 'nothing legal to take');
                Assert::false($technique->IsActive, 'disarmed');
            },

            'adversary left only an illegal attachment: no steal' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $foe->Attachments = [];
                $this->equip($world, $foe, new _01073());
                $this->endOfRound($world, $technique, $foe->Id);

                Assert::same(0, $this->steals($world), 'Cavalier Hat cannot equip to Raton');
            },

            // ---- clearing ----
            'canceled technique clears and never steals' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = $technique->Id;
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);

                Assert::false($technique->IsActive, 'disarmed');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
                $this->endOfRound($world, $technique, $foe->Id);
                Assert::same(0, $this->steals($world), 'canceled');
            },

            'cancel of a different technique leaves this one armed' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = 'someOtherTechnique';
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);

                Assert::true($technique->IsActive, 'still armed');
            },

            // WHY (journal 2026-09-18): Resolve runs before Accept/Reject; a refused challenge must not leak the arm into a later duel.
            'challenge rejected by the adversary clears the arm' => function () {
                $world = new TestWorld();
                [$raton, $foe, $technique] = $this->challenge($world);
                $this->resolve($world, $technique, 0);

                $event = new EventChallengeRejected();
                $event->challengerId = $raton->Id;
                $event->targetId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->IsActive, 'disarmed');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
            },

            'someone else\'s rejected challenge does not clear the arm' => function () {
                $world = new TestWorld();
                [$raton, $foe, $technique] = $this->challenge($world);
                $this->resolve($world, $technique, 0);

                $event = new EventChallengeRejected();
                $event->challengerId = $foe->Id;
                $event->targetId = $raton->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($technique->IsActive, 'still armed');
            },

            'duel end clears the arm so it cannot leak into a later duel' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->IsActive, 'disarmed');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
                $this->endOfRound($world, $technique, $foe->Id);
                Assert::same(0, $this->steals($world), 'no stale steal');
            },

            // ---- chooser args ----
            // WHY: AdversaryId is already cleared when the transition fires; args read the duel round actor instead.
            'chooser args list legal adversary attachments by id and name' => function () {
                $world = new TestWorld();
                [, $foe, $technique, $flint] = $this->duel($world);
                $this->equip($world, $foe, new _01073());
                $world->theah->duelActor = $foe;

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_END_OF_ROUND_01096, 'duelEndOfRound_01096');

                Assert::count(1, $args['attachments'], 'illegal hat filtered out');
                Assert::same($flint->Id, $args['attachments'][0]['id'], 'Flintlock id');
                Assert::same($flint->Name, $args['attachments'][0]['name'], 'Flintlock name');
            },

            'chooser args are empty without a duel round actor' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->theah->duelActor = null;

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_END_OF_ROUND_01096, 'duelEndOfRound_01096');
                Assert::same([], $args['attachments'], 'no actor');
            },

            // ---- steal ----
            'choosing an attachment unequips it, equips it to Raton, marks Used and finishes' => function () {
                $world = new TestWorld();
                [$raton, $foe, $technique, $flint] = $this->duel($world);
                $world->theah->duelActor = $foe;

                $technique->actFromTechniqueWithId($world->game, States::DUEL_END_OF_ROUND_01096, 'duelEndOfRound_01096', $flint->Id);

                $unequips = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequips, 'unequipped');
                Assert::same($foe->Id, $unequips[0]->characterId, 'from adversary');
                Assert::same($flint->Id, $unequips[0]->attachmentId, 'the chosen attachment');
                Assert::same($foe->ControllerId, $unequips[0]->playerId, 'adversary controller');

                $equips = $world->theah->queuedOfType(EventAttachmentEquipping::class);
                Assert::count(1, $equips, 'equipped');
                Assert::same($raton->Id, $equips[0]->characterId, 'onto Raton');
                Assert::same($flint->Id, $equips[0]->attachmentId, 'the chosen attachment');
                Assert::same($raton->ControllerId, $equips[0]->playerId, 'Raton controller');
                Assert::same(0, $equips[0]->cost, 'free: Technique steal pays no wealth');
                Assert::same(0, $equips[0]->discount, 'no discount needed');
                Assert::true($equips[0]->asAction, 'as an effect');
                Assert::same($raton->Id, $equips[0]->sourceId, 'source Raton');
                Assert::same($technique->Id, $equips[0]->abilityId, 'ability id');

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::true(
                    array_search(EventAttachmentUnequipped::class, $order, true) < array_search(EventAttachmentEquipping::class, $order, true),
                    'unequip is queued before equip'
                );
                Assert::true($technique->Used, 'Technique used');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState()');
            },

            'choosing refuses an unknown attachment id' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $world->theah->duelActor = $foe;
                Assert::true($this->actThrows($world, $technique, 999999), 'invalid id');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'choosing refuses an attachment not equipped to the adversary' => function () {
                $world = new TestWorld();
                [$raton, $foe, $technique] = $this->duel($world);
                $world->theah->duelActor = $foe;
                $mine = $this->equip($world, $raton, new _01049());
                Assert::true($this->actThrows($world, $technique, $mine->Id), 'not on adversary');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'choosing refuses an attachment Raton cannot equip' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $world->theah->duelActor = $foe;
                $hat = $this->equip($world, $foe, new _01073());
                Assert::true($this->actThrows($world, $technique, $hat->Id), 'Duelist-only hat');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::false($technique->Used, 'not used');
            },
        ];
    }
}
