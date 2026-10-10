<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01133_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01133';
    }

    /** @return array{0:_01133,1:Reaction_01133,2:GenericCharacter} */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01133(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Sorcerer', ['Sorcerer']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        /** @var Reaction_01133 $reaction */
        $reaction = $risk->getReactions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        $world->game->globals->set(Game::CHOSEN_ACTION, $risk->getActions()[0]->Id);
        return [$risk, $reaction, $performer];
    }

    private function enteringPay(TestWorld $world, int $cardId): EventEnteringPayState
    {
        $event = new EventEnteringPayState();
        $event->playerId = 1;
        $event->cardId = $cardId;
        $event->payStateType = Game::PAY_STATE_IN_HAND_ACTION;
        $event->internalId = '';
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'buttons offer Engage and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['engage', 'pass'], $ids, 'buttons');
            },

            'EnteringPayState clears WillEngage and offers when performer is unengaged' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $risk->WillEngage = true;

                $reaction->handleEvent($this->enteringPay($world, $risk->Id));

                Assert::false($risk->WillEngage, 'cleared');
                Assert::true($risk->IsUpdated, 'marked updated');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                // WHY: stacked in front of later pay discount calc (journal comment in Reaction).
                Assert::same($transitions[0], $world->theah->queuedEvents[0], 'stacked first');
            },

            'does not offer when the performer is already engaged' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $performer] = $this->scene($world);
                $performer->Engaged = true;

                $reaction->handleEvent($this->enteringPay($world, $risk->Id));

                Assert::false($risk->WillEngage, 'still cleared');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no offer');
            },

            'does not offer for a different card\'s pay state' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $other = $world->placeCard(new _01133(), Game::LOCATION_HAND, 1);

                $reaction->handleEvent($this->enteringPay($world, $other->Id));

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'other card');
            },

            // SUSPECTED BUG / pin: WillEngage reset is gated on Location==HAND, but the
            // reaction offer only checks cardId + unengaged performer — no hand gate.
            // EnteringPayState for in-hand Actions normally only fires while in hand.
            'EnteringPay while not in hand still offers (hand gate only clears WillEngage)' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $risk->WillEngage = true;
                $risk->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->enteringPay($world, $risk->Id));

                Assert::true($risk->WillEngage, 'WillEngage not cleared off-hand');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'still offered');
            },

            'engage sets WillEngage, queues CardEngaged, recalcs discount, and sets ABNORMAL_FLOW' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $performer] = $this->scene($world);
                $reaction->handleEvent($this->enteringPay($world, $risk->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'engage');

                Assert::true($risk->WillEngage, 'WillEngage');
                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($performer->Id, $engages[0]->cardId, 'performer');
                Assert::same($risk->Id, $engages[0]->sourceId, 'source');
                Assert::same($reaction->Id, $engages[0]->abilityId, 'ability');
                Assert::true($world->game->globals->get(Game::ABNORMAL_FLOW) === true, 'abnormal');
                Assert::same(1, $world->game->globals->get(Game::DISCOUNT), 'recalc WealthCost discount');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass leaves WillEngage false but still sets ABNORMAL_FLOW' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->enteringPay($world, $risk->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($risk->WillEngage, 'not engaged');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                Assert::true($world->game->globals->get(Game::ABNORMAL_FLOW) === true, 'abnormal');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
