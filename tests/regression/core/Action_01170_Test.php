<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01166;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01170;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01170;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;

class Action_01170_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01170';
    }

    /** @return array{0:_01170,1:Action_01170} */
    private function scene(TestWorld $world, bool $inHand = true): array
    {
        $risk = $world->placeCard(
            new _01170(),
            $inHand ? Game::LOCATION_HAND : Game::LOCATION_PURGATORY,
            1
        );
        /** @var Action_01170 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action];
    }

    private function trigger(TestWorld $world, Action_01170 $action): void
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is a RiskAction' => function () {
                Assert::instanceOf(RiskAction::class, new Action_01170(), 'RiskAction');
            },

            'available when Opulence is in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'in hand');
            },

            'unavailable when Opulence is not in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'purgatory');
            },

            // WHY (journal 2026-03-31-10): "Discard your hand" is the cost (left of •), not an
            // effect — asEffect/AsPayment/AsPlayed stay false so Reaction_01099a does not fire.
            // Framework parks Opulence in Purgatory before trigger, so it is not in the hand loop.
            'trigger discards remaining hand as cost, draws one, and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world, false);
                $other = $world->placeCard(new _01166(), Game::LOCATION_HAND, 1);
                $also = $world->placeCard(new _01166(), Game::LOCATION_HAND, 1);

                $this->trigger($world, $action);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(2, $discards, 'two hand cards');
                $ids = array_map(fn($e) => $e->cardId, $discards);
                Assert::true(in_array($other->Id, $ids, true), 'other');
                Assert::true(in_array($also->Id, $ids, true), 'also');
                Assert::false(in_array($risk->Id, $ids, true), 'Opulence already out of hand');
                Assert::false($discards[0]->AsPayment, 'not payment');
                Assert::false($discards[0]->AsPlayed, 'not played');
                Assert::false($discards[0]->asEffect, 'not effect');
                Assert::same($risk->Id, $discards[0]->sourceId, 'source');

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw one');
                Assert::same(1, $draws[0]->playerId, 'drawer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'trigger with empty hand still draws and resolves' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);

                $this->trigger($world, $action);

                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'empty');
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'draw');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'trigger ignores a different action id' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $world->placeCard(new _01166(), Game::LOCATION_HAND, 1);

                $event = new EventActionTriggered();
                $event->actionId = 'other';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
