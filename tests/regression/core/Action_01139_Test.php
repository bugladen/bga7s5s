<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;

class Action_01139_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01139';
    }

    /** @return array{0:_01139,1:Action_01139} */
    private function scene(TestWorld $world, int $renown = 1): array
    {
        $risk = $world->placeCard(new _01139(), Game::LOCATION_HAND, 1);
        $world->game->setPlayerReknown(1, $renown);
        /** @var Action_01139 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action];
    }

    private function trigger(TestWorld $world, Action_01139 $action): void
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
                Assert::instanceOf(RiskAction::class, new Action_01139(), 'RiskAction');
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

            'trigger spends Renown, sets EXTRA_ACTIONS to 2, and stamps goToLocker' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world, 2);

                $this->trigger($world, $action);

                $loses = $world->theah->queuedOfType(EventPlayerLosesReknown::class);
                Assert::count(1, $loses, 'lose Renown');
                Assert::same(1, $loses[0]->playerId, 'player');
                Assert::same(1, $loses[0]->amount, 'one');
                Assert::same(2, $world->game->globals->get(Game::EXTRA_ACTIONS), 'two extra actions');
                Assert::true($risk->goToLocker, 'locker flag');
                $notes = array_filter($world->game->notify->messages, fn($m) => $m['type'] === 'extraActions');
                Assert::count(1, $notes, 'extraActions notify');
            },

            // WHY: same Unique spend→Locker pattern as Action_01168 — ActionResolved must fire
            // even though locker move is deferred via goToLocker on discard-from-hand.
            'trigger queues ActionResolved' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1);

                $this->trigger($world, $action);

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'trigger ignores a different action id' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world, 1);

                $event = new EventActionTriggered();
                $event->actionId = 'other';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
                Assert::false($risk->goToLocker, 'flag untouched');
            },
        ];
    }
}
