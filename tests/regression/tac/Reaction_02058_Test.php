<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02058;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02058_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02058';
    }

    /**
     * @return array{0:_02058,1:Reaction_02058,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world, array $traits = []): array
    {
        $risk = $world->placeCard(new _02058(), Game::LOCATION_HAND, 1);
        $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Hero', $traits), Game::LOCATION_CITY_FORUM, 1);
        /** @var Reaction_02058 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $defender, $performer];
    }

    private function issued(TestWorld $world, int $defenderId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->defenderId = $defenderId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_02058(), 'RiskReaction');
            },

            // WHY: Home is adjacent to every city location. The performer may already be Home.
            // Engage is a separate event; the move itself does not engage.
            'a challenge against you offers an unengaged adjacent performer' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $defender, $performer] = $this->scene($world);
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);
                $engaged = $world->placeCharacter(new GenericCharacter('Busy'), Game::LOCATION_CITY_FORUM, 1);
                $engaged->Engaged = true;
                $same = $world->placeCharacter(new GenericCharacter('Here'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Bazaar'), Game::LOCATION_CITY_BAZAAR, 1);

                $reaction->handleEvent($this->issued($world, $defender->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['intervene-' . $performer->Id, 'intervene-' . $home->Id, 'pass'], $ids, 'forum and home');
                Assert::false(in_array('intervene-' . $engaged->Id, $ids, true), 'engaged');
                Assert::false(in_array('intervene-' . $same->Id, $ids, true), 'same location');
                Assert::false(in_array('intervene-' . $far->Id, $ids, true), 'bazaar is not adjacent to the docks');
            },

            'Ole\'s Inn becomes adjacent to the Docks at three players' => function () {
                $world = new TestWorld();
                [, $reaction, $defender, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_CITY_OLES_INN;
                $reaction->handleEvent($this->issued($world, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'two-player map');

                $world->game->globals->set(Game::PLAYER_COUNT, 3);
                $reaction->handleEvent($this->issued($world, $defender->Id));
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['intervene-' . $performer->Id, 'pass'], $ids, 'three-player map');
            },

            'challenge types that forbid intervention, or that narrow who may intervene, filter the offer' => function () {
                $world = new TestWorld();
                [, $reaction, $defender, $performer] = $this->scene($world, ['Duelist']);
                foreach ([
                    Game::VALERI_MIKHAILOV_CHALLENGE_TYPE,
                    Game::TORVO_ESPADA_CHALLENGE_TYPE,
                    Game::RAVEN_CHALLENGE_TYPE,
                ] as $type) {
                    $world->theah->takeQueuedEvents();
                    $world->game->globals->set(Game::CHALLENGE_TYPE, $type);
                    $reaction->handleEvent($this->issued($world, $defender->Id));
                    Assert::count(0, $world->theah->queuedEvents, (string) $type);
                }

                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::LEGENDARY_REPUTATION_CHALLENGE_TYPE);
                $reaction->handleEvent($this->issued($world, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'duelist is not a leader');
                $performer->addTrait($world->game, 'Leader');
                $reaction->handleEvent($this->issued($world, $defender->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'leader');

                $world->theah->takeQueuedEvents();
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::AJA_CHALLENGE_TYPE);
                $performer->ModifiedFinesse = 2;
                $reaction->handleEvent($this->issued($world, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'finesse 2');
                $performer->ModifiedFinesse = 3;
                $reaction->handleEvent($this->issued($world, $defender->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'finesse 3');
            },

            'does not offer for a canceled challenge, an opponent defender, outside the hand, or when used' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $defender] = $this->scene($world);
                $event = $this->issued($world, $defender->Id);
                $event->canceled = true;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'canceled');

                $defender->ControllerId = 2;
                $event->canceled = false;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'not your defender');

                $defender->ControllerId = 1;
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'not in hand');

                $risk->Location = Game::LOCATION_HAND;
                $reaction->Used = true;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'choosing a performer enters the in-hand pay state and does not spend the reaction yet' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $defender, $performer] = $this->scene($world, ['Hero']);
                $reaction->handleEvent($this->issued($world, $defender->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'intervene-' . $performer->Id);

                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $pay[0]->payStateType, 'type');
                Assert::same($risk->Id, $pay[0]->cardId, 'card');
                Assert::same($reaction->Id, $pay[0]->internalId, 'reaction');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                $explanations = [];
                Assert::same(1, $risk->getReactionFromHandDiscount($world->theah, $reaction, $explanations), 'hero discount');
            },

            'pass does not pay' => function () {
                $world = new TestWorld();
                [, $reaction, $defender] = $this->scene($world, ['Hero']);
                $reaction->handleEvent($this->issued($world, $defender->Id));
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
                $explanations = [];
                Assert::same(0, $reaction->getReactionFromHandDiscount($world->theah, $reaction, $explanations), 'no performer');
            },

            // WHY: interventionCheck would reject an adjacent engaged mover, so the swap of
            // Defender, CHOSEN_TARGET, and CHALLENGE_ACCEPTED is done directly. Engage and
            // move are queued, and the move does not itself engage.
            'paying moves the performer in and makes them the defender' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $defender, $performer] = $this->scene($world);
                $defender->addCondition(Game::DUEL_DEFENDER);
                $world->game->globals->set(Game::CHOSEN_TARGET, $defender->Id);
                $reaction->handleEvent($this->issued($world, $defender->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'intervene-' . $performer->Id);
                $world->theah->takeQueuedEvents();

                $event = new EventRiskReactionTriggered();
                $event->internalId = $reaction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($reaction->Used, 'used');
                Assert::false($defender->hasCondition(Game::DUEL_DEFENDER), 'old defender');
                Assert::true($performer->hasCondition(Game::DUEL_DEFENDER), 'new defender');
                Assert::same($performer->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::true($world->game->globals->get(Game::CHALLENGE_ACCEPTED), 'accepted');
                $engage = $world->theah->queuedOfType(EventCardEngaged::class);
                $move = $world->theah->queuedOfType(EventCardMoving::class);
                $intervened = $world->theah->queuedOfType(EventCharacterIntervened::class);
                Assert::same($performer->Id, $engage[0]->cardId, 'engage');
                Assert::same($performer->Id, $move[0]->cardId, 'move');
                Assert::same(Game::LOCATION_CITY_FORUM, $move[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_DOCKS, $move[0]->toLocation, 'to the challenge');
                Assert::false($move[0]->engage, 'move does not engage');
                Assert::same($defender->Id, $intervened[0]->oldTargetId, 'old');
                Assert::same($performer->Id, $intervened[0]->newTargetId, 'new');
                Assert::same($risk->Id, $engage[0]->sourceId, 'source');
            },

            'a Knight discounts the reaction and a turn end clears the discount' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $defender, $performer] = $this->scene($world, ['Knight']);
                $reaction->handleEvent($this->issued($world, $defender->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'intervene-' . $performer->Id);
                $explanations = [];
                Assert::same(1, $reaction->getReactionFromHandDiscount($world->theah, $reaction, $explanations), 'knight');

                $risk->Location = Game::LOCATION_PLAYER_HOME;
                $explanations = [];
                Assert::same(0, $reaction->getReactionFromHandDiscount($world->theah, $reaction, $explanations), 'not in hand');
                $risk->Location = Game::LOCATION_HAND;

                $end = new EventPlayerTurnEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);
                $explanations = [];
                Assert::same(0, $reaction->getReactionFromHandDiscount($world->theah, $reaction, $explanations), 'latch cleared');
                Assert::same(['pass'], array_column($reaction->getReactionButtonProperties($world->theah), 'reaction'), 'no location');
            },
        ];
    }
}
