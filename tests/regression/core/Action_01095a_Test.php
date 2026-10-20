<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01095;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01095a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;

class Action_01095a_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01095a';
    }

    /** @return array{0:_01095,1:Action_01095a} */
    private function scene(TestWorld $world, string $at = Game::LOCATION_CITY_DOCKS): array
    {
        $patricia = $world->placeCharacter(new _01095(), $at, 1);
        /** @var Action_01095a $action */
        $action = $patricia->getActions()[0];
        return [$patricia, $action];
    }

    private function trigger(TestWorld $world, Action_01095a $action): void
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
            'available at the Docks when it can be claimed' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: unlike 95b there is no engage cost, so Engaged status is irrelevant to 95a.
            'available while engaged too (no engage requirement)' => function () {
                $world = new TestWorld();
                [$patricia, $action] = $this->scene($world);
                $patricia->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable away from the Docks' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_FORUM);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Forum');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_PLAYER_HOME);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            // WHY: Indomitable Will (Action_01130) toggles CanBeClaimed; the shared theah rule must be honoured.
            'unavailable when the Docks cannot be claimed' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unclaimable');
            },

            'unavailable to the opponent' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'not controller');
            },

            'unavailable when Fate\'s Silence blanks Patricia' => function () {
                $world = new TestWorld();
                [$patricia, $action] = $this->scene($world);
                $patricia->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger claims the Docks for Patricia\'s controller and resolves' => function () {
                $world = new TestWorld();
                [$patricia, $action] = $this->scene($world);

                $this->trigger($world, $action);

                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claims, 'one claim');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'Docks');
                Assert::same(1, $claims[0]->playerId, 'controller');
                Assert::same($patricia->Id, $claims[0]->performerId, 'performer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::instanceOf(EventLocationClaimed::class, $world->theah->queuedEvents[0], 'claim before resolve');
            },

            // WHY: the action still resolves (and is paid) if the location became unclaimable between availability and resolution.
            'trigger on an unclaimable Docks announces it, claims nothing, but still resolves' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;

                $this->trigger($world, $action);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                $notes = array_filter($world->game->notify->messages, fn($m) => $m['type'] === 'message');
                Assert::count(1, $notes, 'announced');
            },

            'trigger for another action id is ignored' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
