<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01168;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01168;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;

class Action_01168_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01168';
    }

    /** @return array{0:_01168,1:Action_01168} */
    private function scene(TestWorld $world, int $renown = 1): array
    {
        $risk = $world->placeCard(new _01168(), Game::LOCATION_HAND, 1);
        $world->game->setPlayerReknown(1, $renown);
        /** @var Action_01168 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action];
    }

    private function trigger(TestWorld $world, Action_01168 $action): void
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
                Assert::instanceOf(RiskAction::class, new Action_01168(), 'RiskAction');
            },

            'available when the player has at least one Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'has Renown');
            },

            'unavailable with zero Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'broke');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world, 1);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            // WHY (journal 2026-10-10-09): contrast Action_01139 — 01168 queues remove-from-discard
            // + locker + ActionResolved on trigger (not a deferred goToLocker stamp).
            'trigger spends Renown, draws two, removes from discard, sends to locker, and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world, 2);

                $this->trigger($world, $action);

                $loses = $world->theah->queuedOfType(EventPlayerLosesReknown::class);
                Assert::count(1, $loses, 'lose Renown');
                Assert::same(1, $loses[0]->playerId, 'player');
                Assert::same(1, $loses[0]->amount, 'one');

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(2, $draws, 'draw two');
                Assert::same(1, $draws[0]->playerId, 'drawer');

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                Assert::count(1, $removed, 'leave discard');
                Assert::same($risk->Id, $removed[0]->cardId, 'this card');

                $lockers = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::count(1, $lockers, 'locker');
                Assert::same($risk->Id, $lockers[0]->cardId, 'this card');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'trigger queues ActionResolved (contrast former Action_01139 gap)' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1);

                $this->trigger($world, $action);

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'trigger ignores a different action id' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1);

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
