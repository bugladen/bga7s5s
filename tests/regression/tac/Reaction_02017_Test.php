<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02017;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02017_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02017';
    }

    /**
     * Panzerhand on foe (adversary); yours is duel actor. Combat card with Riposte>0 in DB.
     *
     * @return array{0:_02017,1:Reaction_02017,2:GenericCharacter,3:GenericCharacter,4:_02006}
     */
    private function duel(TestWorld $world): array
    {
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $panzer = $world->placeCard(new _02017(), Game::LOCATION_CITY_DOCKS, 2);
        $panzer->AttachedToId = $foe->Id;
        $foe->Attachments[] = $panzer->Id;

        $combat = $world->placeCard(new _02006(), Game::LOCATION_HAND, 1);
        Assert::true($combat->Riposte > 0, 'riposte fixture');

        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);

        /** @var Reaction_02017 $reaction */
        $reaction = $panzer->getReactions()[0];
        return [$panzer, $reaction, $actor, $foe, $combat];
    }

    private function announce(TestWorld $world, int $cardId): EventCombatCardAnnounced
    {
        $event = new EventCombatCardAnnounced();
        $event->playerId = 1;
        $event->cardId = $cardId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is an AttachmentReaction' => function () {
                Assert::instanceOf(AttachmentReaction::class, new Reaction_02017(), 'AttachmentReaction');
            },

            'buttons offer -1 Riposte and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->duel($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['1Riposte', 'pass'], $ids, 'buttons');
            },

            // WHY: offer when in duel, owning character is adversary, combat card is faction with Riposte>0.
            'offers on EventCombatCardAnnounced when adversary and Riposte combat card' => function () {
                $world = new TestWorld();
                [$panzer, $reaction, , , $combat] = $this->duel($world);

                $reaction->handleEvent($this->announce($world, $combat->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($panzer->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same($combat->Id, $reaction->targetCombatCardId, 'armed');
            },

            'does not offer when owning character is the duel actor' => function () {
                $world = new TestWorld();
                [, $reaction, $actor, $foe, $combat] = $this->duel($world);
                $world->theah->duelActor = $foe;
                $world->theah->duelOpponent = $actor;

                $reaction->handleEvent($this->announce($world, $combat->Id));
                Assert::count(0, $world->theah->queuedEvents, 'actor owns panzer');
            },

            'does not offer when combat card has no Riposte' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->duel($world);
                $zero = $world->placeCard(new _02018(), Game::LOCATION_HAND, 1);
                Assert::same(0, $zero->Riposte, 'fixture');

                $reaction->handleEvent($this->announce($world, $zero->Id));
                Assert::count(0, $world->theah->queuedEvents, 'no riposte');
            },

            'does not offer outside a duel' => function () {
                $world = new TestWorld();
                [, $reaction, , , $combat] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);

                $reaction->handleEvent($this->announce($world, $combat->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not in duel');
            },

            '1Riposte sets Used and keeps targetCombatCardId' => function () {
                $world = new TestWorld();
                [, $reaction, , , $combat] = $this->duel($world);
                $reaction->targetCombatCardId = $combat->Id;

                $reaction->performReaction($world->game, 0, $reaction->Id, '1Riposte');

                Assert::true($reaction->Used, 'used');
                Assert::same($combat->Id, $reaction->targetCombatCardId, 'kept');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass clears targetCombatCardId without setting Used' => function () {
                $world = new TestWorld();
                [, $reaction, , , $combat] = $this->duel($world);
                $reaction->targetCombatCardId = $combat->Id;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::same(null, $reaction->targetCombatCardId, 'cleared');
                Assert::false($reaction->Used, 'not used');
            },

            'EventDuelCalculateCombatCardStats removes 1 Riposte when target matches' => function () {
                $world = new TestWorld();
                [, $reaction, , , $combat] = $this->duel($world);
                $reaction->targetCombatCardId = $combat->Id;

                $event = new EventDuelCalculateCombatCardStats();
                $event->combatCardId = $combat->Id;
                $event->theah = $world->theah;
                $event->addRiposte(2);
                $reaction->handleEvent($event);

                Assert::same(1, $event->riposte, '-1 Riposte');
                Assert::count(1, $event->explanations, 'explained');
            },

            'EventDuelCalculateCombatCardStats ignores non-matching combat card' => function () {
                $world = new TestWorld();
                [, $reaction, , , $combat] = $this->duel($world);
                $reaction->targetCombatCardId = $combat->Id;

                $event = new EventDuelCalculateCombatCardStats();
                $event->combatCardId = $combat->Id + 999;
                $event->theah = $world->theah;
                $event->addRiposte(2);
                $reaction->handleEvent($event);

                Assert::same(2, $event->riposte, 'unchanged');
            },
        ];
    }
}
