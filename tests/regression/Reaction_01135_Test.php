<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01135;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01135;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelActionsDone;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01135_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01135';
    }

    /** @return array{0:_01135,1:Reaction_01135,2:GenericCharacter} */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01135(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        /** @var Reaction_01135 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $actor];
    }

    private function announce(TestWorld $world, int $playerId, int $cardId): EventCombatCardAnnounced
    {
        $event = new EventCombatCardAnnounced();
        $event->playerId = $playerId;
        $event->cardId = $cardId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a cancel RiskReaction' => function () {
                Assert::instanceOf(ICancelReaction::class, new Reaction_01135(), 'ICancelReaction');
            },

            'buttons offer Gamble and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['gamble', 'pass'], $ids, 'buttons');
            },

            'offers when an opponent announces a combat card while this Risk is in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $combat = $world->placeCard(new _01135(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->announce($world, 2, $combat->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'owner chooses');
            },

            'does not offer for own combat card announce' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $combat = $world->placeCard(new _01135(), Game::LOCATION_HAND, 1);

                $reaction->handleEvent($this->announce($world, 1, $combat->Id));

                Assert::count(0, $world->theah->queuedEvents, 'own');
            },

            'does not offer when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                $combat = $world->placeCard(new _01135(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->announce($world, 2, $combat->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
            },

            'does not offer once Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;
                $combat = $world->placeCard(new _01135(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->announce($world, 2, $combat->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'gamble stacks pay transitions and finishes' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'gamble');

                Assert::instanceOf(EventEnteringPayState::class, $world->theah->queuedEvents[0], 'pay on top');
                Assert::same($risk->Id, $world->theah->queuedEvents[0]->cardId, 'card');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedEvents[0]->payStateType, 'reaction pay');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass finishes without pay' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY (journal 2026-09-12): forced replacement must be GAMBLE_TYPE_FREE and clear
            // duel_round.gambled so it does not count against played gambles.
            'RiskReactionTriggered discards the combat card and arms a free gamble' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $combat = $world->placeCard(new _01135(), Game::LOCATION_HAND, 2);
                $reaction->handleEvent($this->announce($world, 2, $combat->Id));
                $world->theah->takeQueuedEvents();
                $world->game->globals->set(Game::DUEL_ID, 1);
                $world->game->globals->set(Game::DUEL_ROUND, 1);

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($combat->Id, $discards[0]->cardId, 'combat card');
                Assert::same($risk->Id, $discards[0]->sourceId, 'source');
                Assert::true($discards[0]->asEffect, 'as effect');
                Assert::same(Game::GAMBLE_TYPE_FREE, $world->game->globals->get(Game::GAMBLE_TYPE), 'free');
                Assert::true($world->game->globals->get(Game::GAMBLE_REVEAL_COUNT) >= 1, 'reveal count');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, '01135');
                Assert::same('01135', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'adversary gambles');
            },

            // WHY (journal 2026-09-19): starter decks run two copies; clear sibling reaction
            // transitions so a leftover prompt cannot nest another 01135 in SETUP_EVENTS.
            'RiskReactionTriggered clears sibling Mireli reaction transitions in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $sibling = $world->placeCard(new _01135(), Game::LOCATION_HAND, 1);
                /** @var Reaction_01135 $siblingReaction */
                $siblingReaction = $sibling->getReactions()[0];
                $combat = $world->placeCard(new _01135(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->announce($world, 2, $combat->Id));
                $siblingReaction->handleEvent($this->announce($world, 2, $combat->Id));
                Assert::count(2, $world->theah->queuedOfType(EventTransition::class), 'two offers');

                $world->game->globals->set(Game::DUEL_ID, 1);
                $world->game->globals->set(Game::DUEL_ROUND, 1);
                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                $siblingOffers = array_filter(
                    $world->theah->queuedOfType(EventTransition::class),
                    fn($t) => $t->internalId === $siblingReaction->Id || $t->sourceId === $sibling->Id
                );
                Assert::count(0, $siblingOffers, 'sibling cleared');
            },

            'DuelActionsDone clears the stored cancelled combat card id' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $combat = $world->placeCard(new _01135(), Game::LOCATION_HAND, 2);
                $reaction->handleEvent($this->announce($world, 2, $combat->Id));

                $done = new EventDuelActionsDone();
                $done->theah = $world->theah;
                $reaction->handleEvent($done);

                Assert::true($risk->IsUpdated, 'cleared storage marks updated');
            },
        ];
    }
}
