<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01095;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01095b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01095b_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01095b';
    }

    /**
     * En garde Patricia (P1) at the Docks; P1 is first player unless $first is false.
     *
     * @return array{0:_01095,1:Action_01095b}
     */
    private function scene(TestWorld $world, bool $first = true): array
    {
        $patricia = $world->placeCharacter(new _01095(), Game::LOCATION_CITY_DOCKS, 1);
        $patricia->Engaged = false;
        $world->game->globals->set(Game::FIRST_PLAYER, $first ? 1 : 2);
        /** @var Action_01095b $action */
        $action = $patricia->getActions()[1];
        return [$patricia, $action];
    }

    private function trigger(TestWorld $world, Action_01095b $action): void
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    private function refused(callable $fn): bool
    {
        try {
            $fn();
        } catch (\BgaUserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            // NOTE: Action_01095b does NOT implement IAbilityThatDependsOnNotBeingFirstPlayer even though its text is
            // first-player dependent, so Lorenzo's Reaction never offers itself for it. Deliberately not pinned either
            // way (looks like a production gap); the override branch below is driven directly via the global.

            'available when en garde at the Docks' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY (journal 2026-03-27-02 bug 1): the cost is "engage her", so an already-engaged Patricia cannot pay it.
            'unavailable when already engaged' => function () {
                $world = new TestWorld();
                [$patricia, $action] = $this->scene($world);
                $patricia->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            // WHY (journal 2026-03-27-02 bug 1): must be The Docks, not just any city location.
            'unavailable at another city location even when en garde' => function () {
                $world = new TestWorld();
                [$patricia, $action] = $this->scene($world);
                $patricia->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Forum');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                [$patricia, $action] = $this->scene($world);
                $patricia->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
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

            // WHY (journal 2026-03-27-02 bug 2/3): engage is a cost, queued first, and the action must resolve on both branches.
            'first player: engages Patricia, draws a card and resolves (no discard prompt)' => function () {
                $world = new TestWorld();
                [$patricia, $action] = $this->scene($world, true);

                $this->trigger($world, $action);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engaged');
                Assert::same($patricia->Id, $engages[0]->cardId, 'Patricia engaged');
                Assert::same($patricia->Id, $engages[0]->sourceId, 'source');
                Assert::same($action->Id, $engages[0]->abilityId, 'ability');
                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same(1, $draws[0]->playerId, 'controller draws');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no discard prompt');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::instanceOf(EventCardEngaged::class, $world->theah->queuedEvents[0], 'engage first');
                Assert::instanceOf(EventActionResolved::class, $world->theah->queuedEvents[2], 'resolve last');
            },

            'not first player: engages Patricia and prompts every opponent to discard instead of drawing' => function () {
                $world = new TestWorld();
                [$patricia, $action] = $this->scene($world, false);

                $this->trigger($world, $action);

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engaged');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'prompt');
                Assert::same('01095', $transitions[0]->transition, 'name');
                Assert::same($patricia->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability id');
                Assert::same(1, $world->game->globals->get(Game::MULTI_STATE_INITIATING_PLAYER), 'initiator excluded from the discard');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved after the prompt');
                Assert::instanceOf(EventTransition::class, $world->theah->queuedEvents[1], 'engage, then prompt');
                Assert::instanceOf(EventActionResolved::class, $world->theah->queuedEvents[2], 'resolve last');
            },

            // WHY: Lorenzo's override makes the (actual) first player resolve as "not first" -> opponents discard.
            'first player with the Lorenzo override takes the discard branch' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, true);
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);

                $this->trigger($world, $action);

                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'discard prompt');
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'still engaged');
            },

            'a non-first player is unaffected by the override flag being false' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, false);

                $this->trigger($world, $action);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'discard prompt');
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

            'discard step: opponent discards a hand card and is released from the multi-state' => function () {
                $world = new TestWorld();
                [$patricia, $action] = $this->scene($world, false);
                $card = $world->placeCharacter(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 2);
                $world->game->currentPlayerId = 2;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01095, 'x', $card->Id);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'one discard');
                Assert::same($card->Id, $discards[0]->cardId, 'card');
                Assert::same(2, $discards[0]->ownerId, 'discarding player');
                Assert::same($patricia->Id, $discards[0]->sourceId, 'source Patricia');
                Assert::false($discards[0]->AsPayment, 'not payment');
                Assert::true($discards[0]->asEffect, 'effect');
                Assert::same([['playerId' => 2, 'transition' => 'multipleOk']], $world->game->gamestate->nonMultiactive, 'released');
            },

            'discard step: refuses an unknown card' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $world->game->currentPlayerId = 2;

                Assert::true(
                    $this->refused(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01095, 'x', 999999)),
                    'not found'
                );
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::count(0, $world->game->gamestate->nonMultiactive, 'still active');
            },

            'discard step: refuses a card that is not in the discarding player\'s hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $world->game->currentPlayerId = 2;
                $patriciasPlayersCard = $world->placeCharacter(new GenericCharacter('P1 Card'), Game::LOCATION_HAND, 1);
                $inPlay = $world->placeCharacter(new GenericCharacter('In Play'), Game::LOCATION_CITY_FORUM, 2);

                Assert::true(
                    $this->refused(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01095, 'x', $patriciasPlayersCard->Id)),
                    'another player\'s hand'
                );
                Assert::true(
                    $this->refused(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01095, 'x', $inPlay->Id)),
                    'not in hand'
                );
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::count(0, $world->game->gamestate->nonMultiactive, 'still active');
            },

            'discard step for another state does nothing' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $card = $world->placeCharacter(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 2);
                $world->game->currentPlayerId = 2;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01093, 'x', $card->Id);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
                Assert::count(0, $world->game->gamestate->nonMultiactive, 'nothing');
            },
        ];
    }
}
