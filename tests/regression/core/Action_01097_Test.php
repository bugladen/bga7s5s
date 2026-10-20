<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01097;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01097;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01097_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01097';
    }

    private function giveHand(TestWorld $world, int $playerId, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $world->placeCard(new GenericCharacter('Hand ' . $playerId . '-' . $i), Game::LOCATION_HAND, $playerId);
        }
    }

    /**
     * Sanjay (player 1, 2 cards in hand) and an opposing character (player 2, 1 card) share Docks.
     *
     * @return array{0:_01097,1:GenericCharacter,2:Action_01097}
     */
    private function scene(TestWorld $world, int $mine = 2, int $theirs = 1): array
    {
        $sanjay = $world->placeCharacter(new _01097(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $this->giveHand($world, 1, $mine);
        $this->giveHand($world, 2, $theirs);
        /** @var Action_01097 $action */
        $action = $sanjay->getActions()[0];
        return [$sanjay, $foe, $action];
    }

    private function act(TestWorld $world, Action_01097 $action, int $id): void
    {
        $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01097, 'highDramaPlayerTurn_01097', $id);
    }

    private function actThrows(TestWorld $world, Action_01097 $action, int $id): bool
    {
        try {
            $this->act($world, $action, $id);
        } catch (UserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            'available when the target controller has fewer cards in hand' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world, 2, 1);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'fewer');
            },

            'available when the target controller has an empty hand' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world, 1, 0);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), '0 < 1');
            },

            // WHY (journal 2026-04-09): "fewer than you" is strict; equal hands must not qualify.
            'unavailable when hands are equal' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world, 2, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'equal');
            },

            'unavailable when the target controller has more cards' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world, 1, 3);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'more');
            },

            'unavailable when both hands are empty' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world, 0, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), '0 < 0 is false');
            },

            'unavailable when the only qualifying target is already engaged' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                $foe->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when the target is at another location' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'different location');
            },

            'a friendly character at the location is not a target' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                $foe->ControllerId = 1;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'own character');
            },

            // WHY: this is a City Action - availability requires Sanjay to be in the city (not Home).
            'unavailable when Sanjay is at Home' => function () {
                $world = new TestWorld();
                [$sanjay, $foe, $action] = $this->scene($world);
                $sanjay->Location = Game::LOCATION_PLAYER_HOME;
                $foe->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'unavailable when Fate\'s Silence blanks Sanjay' => function () {
                $world = new TestWorld();
                [$sanjay, , $action] = $this->scene($world);
                $sanjay->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'unavailable once used' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            'targets are keyed by id and only include characters under the hand limit' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world, 2, 1);
                // Hand count is per controller, so show per-character filtering with an engaged second foe.
                $engaged = $world->placeCharacter(new GenericCharacter('Engaged Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $engaged->Engaged = true;

                $targets = $action->getTargetsForAction(1, $world->theah);
                Assert::same([$foe->Id], array_keys($targets), 'only the en garde qualifying foe');
            },

            'trigger queues the 01097 target-selection transition' => function () {
                $world = new TestWorld();
                [$sanjay, , $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01097', $transitions[0]->transition, 'transition name');
                Assert::same($sanjay->Id, $transitions[0]->sourceId, 'source Sanjay');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal id');
            },

            'trigger for another action is ignored' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            'args carry the performer and the qualifying target ids' => function () {
                $world = new TestWorld();
                [$sanjay, $foe, $action] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sanjay->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01097, 'highDramaPlayerTurn_01097');

                Assert::same($sanjay->Id, $args['performerId'], 'performer');
                Assert::same([$foe->Id], $args['ids'], 'qualifying target');
            },

            'isValidTargetForAbility accepts a same-location opponent with fewer cards' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                [$valid] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($valid, 'valid');
            },

            'isValidTargetForAbility rejects wrong location, own character, and equal hand' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);

                $foe->Location = Game::LOCATION_CITY_FORUM;
                [$valid] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($valid, 'other location');

                $foe->Location = Game::LOCATION_CITY_DOCKS;
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                [$valid] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($valid, 'own character');

                $this->giveHand($world, 2, 1); // theirs now 2 == mine 2
                [$valid] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($valid, 'equal hands');
            },

            'act engages the target (Sanjay as source), resolves the action and transitions' => function () {
                $world = new TestWorld();
                [$sanjay, $foe, $action] = $this->scene($world);

                $this->act($world, $action, $foe->Id);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'one engage');
                Assert::same($foe->Id, $engages[0]->cardId, 'target engaged');
                Assert::same($sanjay->Id, $engages[0]->sourceId, 'source Sanjay');
                Assert::same($action->Id, $engages[0]->abilityId, 'ability id');
                Assert::same($sanjay->ControllerId, $engages[0]->playerId, 'initiating player');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'action resolved');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'named transition');
            },

            'act refuses an unknown character' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                Assert::true($this->actThrows($world, $action, 999999), 'not found');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'act refuses a target whose controller does not have fewer cards' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world, 2, 2);
                Assert::true($this->actThrows($world, $action, $foe->Id), 'equal hands');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'act refuses a target at another location' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::true($this->actThrows($world, $action, $foe->Id), 'other location');
            },

            'act refuses the controller\'s own character' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($this->actThrows($world, $action, $ally->Id), 'own character');
            },
        ];
    }
}
