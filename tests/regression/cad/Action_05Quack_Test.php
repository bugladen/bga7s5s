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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Quack;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Quack;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05Quack_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Quack';
    }

    /**
     * @return array{world: TestWorld, quack: _05Quack, action: Action_05Quack, foe: GenericCharacter, hand: _04039}
     */
    private function scene(): array
    {
        $world = new TestWorld();
        $quack = $world->placeCharacter(new _05Quack(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Wounded Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->Wounds = 1;
        $hand = $world->placeCard(new _04039(), Game::LOCATION_HAND, 2);
        /** @var Action_05Quack $action */
        $action = $quack->getActions()[0];

        return compact('world', 'quack', 'action', 'foe', 'hand');
    }

    public function tests(): array
    {
        return [
            // WHY: Plain Action — Home legal. Need wounded controlled character whose
            // controller has a hand card (mandatory discard after heal).
            'is offered when a wounded controlled character has a discarding controller' => function () {
                ['world' => $world, 'quack' => $quack, 'action' => $action, 'foe' => $foe, 'hand' => $hand] = $this->scene();

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'baseline');

                $quack->Location = Game::LOCATION_PLAYER_HOME;
                $foe->Location = Game::LOCATION_PLAYER_HOME;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'Home legal');
                $quack->Location = Game::LOCATION_CITY_DOCKS;
                $foe->Location = Game::LOCATION_CITY_DOCKS;

                $foe->Wounds = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unwounded');
                $foe->Wounds = 1;

                $hand->Location = $world->game->getPlayerDiscardDeckName(2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty hand');
                $hand->Location = Game::LOCATION_HAND;

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'restored');
            },

            'queues the target chooser on ActionTriggered' => function () {
                ['world' => $world, 'quack' => $quack, 'action' => $action] = $this->scene();

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($quack, $triggered);
                Assert::same('05Quack', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'chooser');
            },

            'heal then transitions to controller discard; excludes empty-hand targets' => function () {
                ['world' => $world, 'action' => $action, 'foe' => $foe, 'hand' => $hand] = $this->scene();
                $empty = $world->placeCharacter(new GenericCharacter('Empty Hand'), Game::LOCATION_CITY_DOCKS, 1);
                $empty->Wounds = 2;

                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05QUACK, '')['ids'];
                Assert::true(in_array($foe->Id, $ids, true), 'foe offered');
                Assert::false(in_array($empty->Id, $ids, true), 'empty-hand excluded');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05QUACK, '', $foe->Id);
                Assert::same($foe->Id, (int)$world->game->globals->get(Game::CHOSEN_TARGET), 'stashed');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'heal');
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('05Quack_2', $transition->transition, 'discard step');
                Assert::same(2, $transition->playerId, 'controller active');
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'to EVENTS');

                // WHY: createTransitionEvent would make controller 2 active; FakeGame does not.
                $world->game->activePlayerId = 2;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05QUACK_2, '', $hand->Id);
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'discard');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same('', end($world->game->gamestate->transitions), 'done');
            },

            'rejects unwounded via isValidTargetForAbility' => function () {
                ['world' => $world, 'action' => $action, 'foe' => $foe] = $this->scene();
                $foe->Wounds = 0;
                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($ok, 'unwounded rejected');
            },

            'rejects discard from the wrong player' => function () {
                ['world' => $world, 'action' => $action, 'foe' => $foe, 'hand' => $hand] = $this->scene();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05QUACK, '', $foe->Id);
                $world->game->activePlayerId = 1;

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05QUACK_2, '', $hand->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'wrong player');
            },
        ];
    }
}
