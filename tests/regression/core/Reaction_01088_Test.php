<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01088;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01088;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01088_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01088';
    }

    /**
     * Player 1 holds You're Embarrassing Yourself in hand; player 2's Mercenary issues the challenge
     * against player 1's Defender.
     *
     * @return array{0:_01088,1:Reaction_01088,2:Character,3:Character}
     */
    private function scene(TestWorld $world, array $challengerTraits = ['Mercenary']): array
    {
        $risk = $world->placeCard(new _01088(), Game::LOCATION_HAND, 1);
        $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 1);
        $challenger = $world->placeCharacter(new GenericCharacter('Challenger', $challengerTraits), Game::LOCATION_CITY_DOCKS, 2);

        /** @var Reaction_01088 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $challenger, $defender];
    }

    private function issued(TestWorld $world, int $challengerId, int $defenderId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        $event->playerId = 2;
        $event->theah = $world->theah;
        return $event;
    }

    private function triggered(TestWorld $world, _01088 $risk, Reaction_01088 $reaction, string $reactionId): EventRiskReactionTriggered
    {
        $event = new EventRiskReactionTriggered();
        $event->playerId = 1;
        $event->sourceId = $risk->Id;
        $event->internalId = $reaction->Id;
        $event->reactionId = $reactionId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a Risk cancel reaction' => function () {
                $reaction = new Reaction_01088();
                Assert::instanceOf(ICancelReaction::class, $reaction, 'ICancelReaction');
                Assert::instanceOf(RiskReaction::class, $reaction, 'RiskReaction');
            },

            'offers when an opposing Mercenary issues a challenge' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $challenger, $defender] = $this->scene($world);

                $reaction->handleEvent($this->issued($world, $challenger->Id, $defender->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction transition');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'this reaction');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source is the Risk');
                Assert::same(1, $transitions[0]->playerId, 'offered to the Risk controller');
            },

            'does not offer for a non-Mercenary challenger' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $defender] = $this->scene($world, []);

                $reaction->handleEvent($this->issued($world, $challenger->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not a Mercenary');
            },

            // WHY: text says "opposing Mercenary" — your own Mercenary's challenge must not be cancellable by you.
            'does not offer for a Mercenary controlled by the Risk controller' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $defender] = $this->scene($world);
                $challenger->ControllerId = 1;

                $reaction->handleEvent($this->issued($world, $challenger->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'own Mercenary');
            },

            'does not offer when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $challenger, $defender] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->issued($world, $challenger->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'hand gate');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $defender] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->issued($world, $challenger->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY: another cancel (Stubborn / Unyielding) already cancelled the issued event — nothing left to cancel.
            'does not offer when the issued challenge is already canceled' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $defender] = $this->scene($world);
                $event = $this->issued($world, $challenger->Id, $defender->Id);
                $event->canceled = true;

                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'already canceled');
            },

            // WHY (2026-09-22 journal): identity comes from event->challengerId, NOT CHOSEN_PERFORMER.
            // A stale performer global pointing at a non-Mercenary must not suppress the offer,
            // and one pointing at a Mercenary must not create one.
            'uses event challengerId, not CHOSEN_PERFORMER' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $defender] = $this->scene($world);

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $defender->Id);
                $reaction->handleEvent($this->issued($world, $challenger->Id, $defender->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered despite stale performer');

                $world->theah->takeQueuedEvents();
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
                $reaction->handleEvent($this->issued($world, $defender->Id, $challenger->Id));
                Assert::count(0, $world->theah->queuedEvents, 'defender is not a Mercenary');
            },

            'unknown challenger id does not offer or crash' => function () {
                $world = new TestWorld();
                [, $reaction, , $defender] = $this->scene($world);

                $reaction->handleEvent($this->issued($world, 987654, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'no challenger');
            },

            // WHY (2026-09-22 journal): offering never cancels in place. 01088 cancels via the
            // CHALLENGE_CANCELLED flag AFTER paying; canceling the issued event itself would skip
            // the issuance hub (runEventHubAfterCards).
            'offering leaves the issued event un-canceled' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $defender] = $this->scene($world);
                $event = $this->issued($world, $challenger->Id, $defender->Id);

                $reaction->handleEvent($event);

                Assert::false($event->canceled, 'issued event untouched');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            'buttons offer Cancel Challenge and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['cancelChallenge', 'pass'], $ids, 'buttons');
            },

            // WHY (2026-09-22 journal): the flag is set AFTER payment, when RiskReactionTriggered fires.
            'triggered cancelChallenge sets CHALLENGE_CANCELLED and marks the reaction used' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $world->game->globals->set(Game::CHALLENGE_CANCELLED, false);

                $reaction->handleEvent($this->triggered($world, $risk, $reaction, 'cancelChallenge'));

                Assert::same(true, $world->game->globals->get(Game::CHALLENGE_CANCELLED), 'cancelled flag');
                Assert::true($reaction->Used, 'used');
                $used = $world->theah->queuedOfType(EventReactionUsed::class);
                Assert::count(1, $used, 'used event');
                Assert::count(1, $world->game->notify->messages, 'announced');
            },

            'triggered for another reaction id does nothing' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $world->game->globals->set(Game::CHALLENGE_CANCELLED, false);

                $event = $this->triggered($world, $risk, $reaction, 'cancelChallenge');
                $event->internalId = 'someOtherReaction';
                $reaction->handleEvent($event);

                Assert::same(false, $world->game->globals->get(Game::CHALLENGE_CANCELLED), 'untouched');
                Assert::false($reaction->Used, 'not used');
            },

            'triggered with a non-cancel reaction id does not cancel' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $world->game->globals->set(Game::CHALLENGE_CANCELLED, false);

                $reaction->handleEvent($this->triggered($world, $risk, $reaction, 'pass'));

                Assert::same(false, $world->game->globals->get(Game::CHALLENGE_CANCELLED), 'untouched');
                Assert::false($reaction->Used, 'not used');
            },

            // WHY (2026-09-22 journal, "stackEvent pay order"): pay events are STACKED (EnteringPay on top,
            // then the pay transition) like Stubborn/Mireli/Night of Drinking. QUEUEING left pay behind
            // other SETUP events and delayed the cancel past the point the challenge continued.
            'performReaction cancelChallenge stacks EnteringPay on top of the pay transition' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'cancelChallenge');

                $queued = $world->theah->queuedEvents;
                Assert::count(3, $queued, 'entering pay + pay transition + activated');

                Assert::instanceOf(EventEnteringPayState::class, $queued[0], 'EnteringPay on top');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $queued[0]->payStateType, 'in-hand reaction pay');
                Assert::same($risk->Id, $queued[0]->cardId, 'card');
                Assert::same($reaction->Id, $queued[0]->internalId, 'reaction');

                Assert::instanceOf(EventTransition::class, $queued[1], 'pay transition second');
                Assert::same('pay', $queued[1]->transition, 'pay');
                Assert::same($reaction->Id, $queued[1]->internalId, 'reaction');

                Assert::instanceOf(EventReactionActivated::class, $queued[2], 'activated last');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                Assert::false($reaction->Used, 'not used until paid');
            },

            'performReaction pass queues nothing and just finishes' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
