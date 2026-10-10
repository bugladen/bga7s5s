<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01140;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01140;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01140_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01140';
    }

    /** @return array{0:_01140,1:Reaction_01140,2:GenericCharacter} */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01140(), Game::LOCATION_HAND, 1);
        $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_01140 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $mine];
    }

    private function moving(
        TestWorld $world,
        int $cardId,
        string $from = Game::LOCATION_CITY_DOCKS,
        string $to = Game::LOCATION_CITY_FORUM
    ): EventCardMoving {
        $event = new EventCardMoving();
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->initiatingPlayerId = 1;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY (journal 2026-10-03-07): Stubborn is the cancel-speed reference for UL / NoD.
            'is a Risk cancel reaction' => function () {
                $reaction = new Reaction_01140();
                Assert::instanceOf(ICancelReaction::class, $reaction, 'ICancelReaction');
                Assert::instanceOf(RiskReaction::class, $reaction, 'RiskReaction');
            },

            'buttons offer Cancel Movement and Decline' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['cancel', 'decline'], $ids, 'buttons');
            },

            'intercepts own character moving: cancels and stacks the reaction offer' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $mine] = $this->scene($world);

                $move = $this->moving($world, $mine->Id);
                $reaction->handleEvent($move);

                Assert::true($move->canceled, 'canceled in place');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'Stubborn');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
                Assert::same($transitions[0], $world->theah->queuedEvents[0], 'stacked in front');
            },

            'does not offer for an opposing character moving' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $move = $this->moving($world, $foe->Id);
                $reaction->handleEvent($move);

                Assert::false($move->canceled, 'not canceled');
                Assert::count(0, $world->theah->queuedEvents, 'no offer');
            },

            'does not offer when Stubborn is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $mine] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;

                $move = $this->moving($world, $mine->Id);
                $reaction->handleEvent($move);

                Assert::false($move->canceled, 'not canceled');
                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction, $mine] = $this->scene($world);
                $reaction->Used = true;

                $move = $this->moving($world, $mine->Id);
                $reaction->handleEvent($move);

                Assert::false($move->canceled, 'not canceled');
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'does not offer for unstoppable moves' => function () {
                $world = new TestWorld();
                [, $reaction, $mine] = $this->scene($world);

                $move = $this->moving($world, $mine->Id);
                $move->unstoppable = true;
                $reaction->handleEvent($move);

                Assert::false($move->canceled, 'unstoppable');
                Assert::count(0, $world->theah->queuedEvents, 'no offer');
            },

            'does not offer when this card already declined the same move' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $mine] = $this->scene($world);

                $move = $this->moving($world, $mine->Id);
                $move->cancelDeclinedByCardIds[] = $risk->Id;
                $reaction->handleEvent($move);

                Assert::false($move->canceled, 'already declined');
                Assert::count(0, $world->theah->queuedEvents, 'no offer');
            },

            // WHY: stackEvent (not queueEvent) so Stubborn/NoD/UL drain before MEDIUM siblings.
            'cancel stacks EnteringPay then pay and marks Used' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $mine] = $this->scene($world);
                $reaction->handleEvent($this->moving($world, $mine->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'cancel');

                $entering = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::count(1, $entering, 'EnteringPay');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $entering[0]->payStateType, 'in-hand reaction');
                Assert::same($risk->Id, $entering[0]->cardId, 'Stubborn');

                $pays = array_filter(
                    $world->theah->queuedOfType(EventTransition::class),
                    fn($t) => $t->transition === 'pay'
                );
                Assert::count(1, $pays, 'pay');
                Assert::same($reaction->Id, array_values($pays)[0]->internalId, 'reaction');

                Assert::true($reaction->Used, 'Used on cancel choice');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'decline re-queues the move marked declined and does not mark Used' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $mine] = $this->scene($world);
                $reaction->handleEvent($this->moving($world, $mine->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                $released = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $released, 'released');
                Assert::same($mine->Id, $released[0]->cardId, 'same character');
                Assert::true(in_array($risk->Id, $released[0]->cancelDeclinedByCardIds, true), 'declined stamp');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'revertCancellation re-queues the held move' => function () {
                $world = new TestWorld();
                [, $reaction, $mine] = $this->scene($world);
                $reaction->handleEvent($this->moving($world, $mine->Id));
                $world->theah->takeQueuedEvents();

                $reaction->revertCancellation($world->theah);

                $released = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $released, 'released');
                Assert::same($mine->Id, $released[0]->cardId, 'same character');
            },

            'triggered cancel after pay only notifies' => function () {
                $world = new TestWorld();
                [, $reaction, $mine] = $this->scene($world);
                $reaction->handleEvent($this->moving($world, $mine->Id));
                $world->theah->takeQueuedEvents();

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                $notes = array_filter($world->game->notify->messages, fn($m) => $m['type'] === 'message');
                Assert::count(1, $notes, 'notify');
            },
        ];
    }
}
