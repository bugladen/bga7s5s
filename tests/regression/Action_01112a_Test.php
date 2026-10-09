<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01112;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01112a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;

class Action_01112a_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01112a';
    }

    /**
     * Carnaval in hand; en garde performer at Docks. P1 is first player unless $first is false.
     *
     * @return array{0:_01112,1:Action_01112a,2:GenericCharacter}
     */
    private function scene(TestWorld $world, bool $first = true): array
    {
        $carnaval = $world->placeCard(new _01112(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $performer->Engaged = false;
        $world->game->globals->set(Game::FIRST_PLAYER, $first ? 1 : 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        /** @var Action_01112a $action */
        $action = $carnaval->getActions()[0];
        return [$carnaval, $action, $performer];
    }

    private function trigger(TestWorld $world, Action_01112a $action): void
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
            'available to the first player with an en garde city performer' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, true);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'first + engarde');
            },

            'unavailable when not the first player' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not first');
            },

            'unavailable when the only performer is already engaged' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when the performer is at Home' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable when the location cannot become uncontrolled' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBecomeUncontrolled = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blocked');
            },

            'unavailable when Carnaval is not in hand' => function () {
                $world = new TestWorld();
                [$carnaval, $action] = $this->scene($world);
                $carnaval->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'getPerformersForAction returns only en garde city characters at claimable sites' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $engaged = $world->placeCharacter(new GenericCharacter('Engaged'), Game::LOCATION_CITY_DOCKS, 1);
                $engaged->Engaged = true;
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($performer->Id, $ids, true), 'engarde');
                Assert::false(in_array($engaged->Id, $ids, true), 'engaged excluded');
                Assert::false(in_array($home->Id, $ids, true), 'Home excluded');
            },

            'trigger engages the performer and makes the location uncontrolled' => function () {
                $world = new TestWorld();
                [$carnaval, $action, $performer] = $this->scene($world);

                $this->trigger($world, $action);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($performer->Id, $engages[0]->cardId, 'performer');
                Assert::same($carnaval->Id, $engages[0]->sourceId, 'Carnaval');
                Assert::same($action->Id, $engages[0]->abilityId, 'action');

                $uncontrolled = $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class);
                Assert::count(1, $uncontrolled, 'uncontrolled');
                Assert::same($performer->Location, $uncontrolled[0]->location, 'performer site');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::instanceOf(EventCardEngaged::class, $world->theah->queuedEvents[0], 'engage first');
            },

            'trigger engages but notifies when uncontrolled is blocked mid-resolve' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                // Available checked earlier; block after selection.
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBecomeUncontrolled = false;

                $this->trigger($world, $action);

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'still engaged');
                Assert::count(0, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class), 'blocked');
                Assert::count(1, $world->game->notify->messages, 'notified');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'still resolves');
            },

            'trigger for another action id is ignored' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'other';
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
