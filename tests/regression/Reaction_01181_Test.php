<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01181;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01181;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01181_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01181';
    }

    /**
     * Sorte Deck equipped on host at Docks; optional wounded target at same location.
     *
     * @return array{0:_01181,1:Reaction_01181,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world, array $hostTraits = []): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host', $hostTraits), Game::LOCATION_CITY_DOCKS, 1);
        $target = $world->placeCharacter(new GenericCharacter('Wounded'), Game::LOCATION_CITY_DOCKS, 2);
        $target->Wounds = 1;
        /** @var _01181 $deck */
        $deck = $world->placeCard(new _01181(), Game::LOCATION_CITY_DOCKS, 1);
        $deck->AttachedToId = $host->Id;
        $host->Attachments[] = $deck->Id;
        /** @var Reaction_01181 $reaction */
        $reaction = $deck->getReactions()[0];
        return [$deck, $reaction, $host, $target];
    }

    private function beingWounded(TestWorld $world, int $characterId, int $wounds = 1): EventCharacterBeingWounded
    {
        $event = new EventCharacterBeingWounded();
        $event->characterId = $characterId;
        $event->wounds = $wounds;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is an AttachmentReaction' => function () {
                Assert::instanceOf(AttachmentReaction::class, new Reaction_01181(), 'AttachmentReaction');
            },

            // WHY (journal 2026-04-09-05): cancel+clone first BeingWounded so heal can
            // set WoundsHealedIncoming before the wound reapplies; skipNextEvent avoids
            // re-offering when the clone re-enters the loop.
            'offers on BeingWounded at this location when not engaged' => function () {
                $world = new TestWorld();
                [$deck, $reaction, , $target] = $this->scene($world);

                $wound = $this->beingWounded($world, $target->Id);
                $reaction->handleEvent($wound);

                Assert::true($wound->canceled, 'canceled');
                Assert::same([$target->Id], $reaction->HealTargetIds, 'target');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($deck->Id, $transitions[0]->sourceId, 'deck');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
            },

            // WHY (journal 2026-04-09-05 bug 1): without !$event->canceled, a prior cancel
            // (e.g. Vittoria) lets Sorte Deck clone a dead event and queue a heal with no wound.
            'does not offer when BeingWounded is already canceled' => function () {
                $world = new TestWorld();
                [, $reaction, , $target] = $this->scene($world);

                $wound = $this->beingWounded($world, $target->Id);
                $wound->canceled = true;
                $reaction->handleEvent($wound);

                Assert::count(0, $world->theah->queuedEvents, 'skipped');
                Assert::same([], $reaction->HealTargetIds, 'no target');
            },

            'does not offer when the attachment is engaged' => function () {
                $world = new TestWorld();
                [$deck, $reaction, , $target] = $this->scene($world);
                $deck->Engaged = true;

                $reaction->handleEvent($this->beingWounded($world, $target->Id));

                Assert::count(0, $world->theah->queuedEvents, 'engaged');
            },

            'does not offer for a wound at another location' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->beingWounded($world, $elsewhere->Id));

                Assert::count(0, $world->theah->queuedEvents, 'wrong location');
            },

            'does not offer once Used' => function () {
                $world = new TestWorld();
                [, $reaction, , $target] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->beingWounded($world, $target->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'does not offer when not attached' => function () {
                $world = new TestWorld();
                [$deck, $reaction, , $target] = $this->scene($world);
                $deck->AttachedToId = 0;

                $reaction->handleEvent($this->beingWounded($world, $target->Id));

                Assert::count(0, $world->theah->queuedEvents, 'unattached');
            },

            'buttons offer Heal 1 and Pass for a non-Strega host' => function () {
                $world = new TestWorld();
                [, $reaction, , $target] = $this->scene($world);
                $reaction->handleEvent($this->beingWounded($world, $target->Id));

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['heal1Wound', 'pass'], $ids, 'buttons');
            },

            // WHY (journal 2026-04-09-05 bug 2): at offer time the wound is still held, so
            // effective wounds = Wounds + stored event wounds — not bare Wounds.
            'Strega host offers Heal 2 when effective wounds exceed 1' => function () {
                $world = new TestWorld();
                [, $reaction, , $target] = $this->scene($world, ['Strega']);
                $target->Wounds = 1;
                $reaction->handleEvent($this->beingWounded($world, $target->Id, 1));

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['heal1Wound', 'heal2Wounds', 'pass'], $ids, 'Strega heal2');
            },

            'heal1 engages deck, releases wound, heals, and marks Used' => function () {
                $world = new TestWorld();
                [$deck, $reaction, , $target] = $this->scene($world);
                $reaction->handleEvent($this->beingWounded($world, $target->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'heal1Wound');

                Assert::true($reaction->Used, 'used');
                Assert::false($reaction->isAvailable(), 'unavailable');
                Assert::same(1, $target->WoundsHealedIncoming, 'incoming heal');
                Assert::same([], $reaction->HealTargetIds, 'cleared');

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($deck->Id, $engages[0]->cardId, 'deck engaged');

                $released = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $released, 'released wound');
                Assert::same($target->Id, $released[0]->characterId, 'target');

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'heal');
                Assert::same($target->Id, $heals[0]->characterId, 'heal target');
                Assert::same(1, $heals[0]->wounds, 'one');
                Assert::same($deck->Id, $heals[0]->sourceId, 'source');
                Assert::same($reaction->Id, $heals[0]->abilityId, 'ability');

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::true(
                    array_search(EventCardEngaged::class, $order, true)
                        < array_search(EventCharacterBeingWounded::class, $order, true),
                    'engage before release'
                );
                Assert::true(
                    array_search(EventCharacterBeingWounded::class, $order, true)
                        < array_search(EventCharacterBeingHealed::class, $order, true),
                    'wound before heal'
                );
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'heal2 heals two wounds for a Strega host' => function () {
                $world = new TestWorld();
                [, $reaction, , $target] = $this->scene($world, ['Strega']);
                $target->Wounds = 1;
                $reaction->handleEvent($this->beingWounded($world, $target->Id, 1));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'heal2Wounds');

                Assert::same(2, $target->WoundsHealedIncoming, 'two');
                Assert::same(2, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->wounds, 'heal 2');
                Assert::true($reaction->Used, 'used');
            },

            'pass releases the wound without engaging or marking Used' => function () {
                $world = new TestWorld();
                [, $reaction, , $target] = $this->scene($world);
                $reaction->handleEvent($this->beingWounded($world, $target->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::true($reaction->isAvailable(), 'still available');
                Assert::same([], $reaction->HealTargetIds, 'shifted');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'released');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'no heal');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'skipNextEvent ignores the released BeingWounded clone' => function () {
                $world = new TestWorld();
                [, $reaction, , $target] = $this->scene($world);
                $reaction->handleEvent($this->beingWounded($world, $target->Id));
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                $released = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];

                $reaction->handleEvent($released);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'skipped re-offer');
            },
        ];
    }
}
