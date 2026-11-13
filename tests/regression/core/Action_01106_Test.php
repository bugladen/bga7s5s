<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01105;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01106;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01106_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01105;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01106;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01106_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01106';
    }

    /**
     * Improvising in hand; opponent discard holds Drinking Games; city performer for stolen City Action.
     *
     * @return array{0:_01106,1:Action_01106,2:_01105}
     */
    private function scene(TestWorld $world): array
    {
        $improv = $world->placeCard(new _01106(), Game::LOCATION_HAND, 1);
        $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $discardName = $world->game->getPlayerDiscardDeckName(2);
        $stolen = $world->placeCard(new _01105(), $discardName, 2);
        /** @var Action_01106 $action */
        $action = $improv->getActions()[0];
        return [$improv, $action, $stolen];
    }

    public function tests(): array
    {
        return [
            'is a RiskAction that targets cards' => function () {
                $action = new Action_01106();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(IAbilityThatTargetsCards::class, $action, 'targets cards');
            },

            'available when an opponent discard has an affordable playable Risk action' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when opponent discard has no Risks' => function () {
                $world = new TestWorld();
                $improv = $world->placeCard(new _01106(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01106 $action */
                $action = $improv->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty discard');
            },

            'unavailable when Improvising is not in hand' => function () {
                $world = new TestWorld();
                [$improv, $action] = $this->scene($world);
                $improv->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            // WHY: Action_01106 skips instanceof self when probing discard Risks so two Improvisings
            // in opposing discards cannot recurse forever through isAvailableToPlayer.
            'skips other Improvising actions in opponent discard (no recursion)' => function () {
                $world = new TestWorld();
                $improv = $world->placeCard(new _01106(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $discardName = $world->game->getPlayerDiscardDeckName(2);
                $world->placeCard(new _01106(), $discardName, 2);
                /** @var Action_01106 $action */
                $action = $improv->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'only Improvising in discard');
            },

            'trigger queues transition 01106' => function () {
                $world = new TestWorld();
                [$improv, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01106', $transitions[0]->transition, 'name');
                Assert::same($improv->Id, $transitions[0]->sourceId, 'source');
            },

            'args step 1 lists opposing players' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->game->activePlayerId = 1;

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01106,
                    'highDramaPlayerTurn_01106'
                );

                Assert::count(1, $args['opponents'], 'one opponent');
                Assert::same(2, $args['opponents'][0]['id'], 'player 2');
            },

            'act step 1 stores CHOSEN_OPPONENT' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01106,
                    'highDramaPlayerTurn_01106',
                    2
                );

                Assert::same(2, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'opponent');
                Assert::same(['opponentChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'args step 2 lists stealable Risk actions from the chosen discard' => function () {
                $world = new TestWorld();
                [, $action, $stolen] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01106_2,
                    'highDramaPlayerTurn_01106_2'
                );

                Assert::count(1, $args['cards'], 'one risk card');
                Assert::same($stolen->Id, $args['cards'][0]['id'], 'Drinking Games');
                Assert::count(1, $args['actions'], 'one action');
                Assert::same($stolen->getActions()[0]->Id, $args['actions'][0]['id'], 'Action_01105');
            },

            // WHY OwnerId = stolen risk's OwnerId, ControllerId = Improvising player (discard/sink vs play).
            'act step 2 hides original, clones into hand, and chooses performer for Drinking Games' => function () {
                $world = new TestWorld();
                [$improv, $action, $stolen] = $this->scene($world);
                /** @var Action_01105 $stolenAction */
                $stolenAction = $stolen->getActions()[0];

                $action->actFromActionWithActionId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01106_2,
                    'highDramaPlayerTurn_01106_2',
                    $stolen->Id,
                    $stolenAction->Id
                );

                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $stolen->Location, 'original hidden');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'removed from discard');

                $clones = array_filter(
                    $world->game->createdCardsInLocation,
                    fn($c) => $c instanceof _01106_RiskClone
                );
                Assert::count(1, $clones, 'one clone');
                /** @var _01106_RiskClone $clone */
                $clone = array_values($clones)[0];
                Assert::same(Game::LOCATION_HAND, $clone->Location, 'in hand');
                Assert::same(2, $clone->OwnerId, 'stolen OwnerId');
                Assert::same(1, $clone->ControllerId, 'Improvising controller');
                Assert::same($stolen->Id, $clone->ClonedCardId, 'cloned id');
                Assert::same($improv->Id, $clone->ParentCardId, 'parent Improvising');
                Assert::same($stolen->Name, $clone->Name, 'name copied');
                Assert::count(1, $clone->getActions(), 'cloned action');

                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToHand::class), 'added to hand');
                Assert::true($world->game->globals->get(Game::ABNORMAL_FLOW), 'abnormal flow');
                Assert::same(
                    'inHandActionChoosePerformer',
                    $world->theah->queuedOfType(EventTransition::class)[0]->transition,
                    'performer chooser (RequiresPerformerSelected)'
                );
                Assert::same(['actionChosen'], $world->game->gamestate->transitions, 'transition');
            },

            // WHY (live report): Improvising → Press the Advantage. Costs paid, target
            // chosen (log: engage and move HOME), but Permanently Hidden on discard
            // dropped the shell from buildCity — CharacterTargeted never reached
            // Action_01058::handleEvent, so no board move. Shell must stay PendingCleanup
            // in discard until the stolen action's ActionResolved.
            'Improvised Press the Advantage still engages and moves after clone discard' => function () {
                $world = new TestWorld();
                $performer = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Combat = 3;
                $performer->ModifiedCombat = 3;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Combat = 1;
                $foe->ModifiedCombat = 1;

                $original = $world->placeCard(new _01058(), Game::LOCATION_PERMANENTLY_HIDDEN, 2);
                $improv = $world->placeCard(new _01106(), 'Discard-1', 1);
                $clone = $world->placeCard(new _01106_RiskClone(), Game::LOCATION_HAND, 1);
                $clone->OwnerId = 2;
                $clone->ControllerId = 1;
                $clone->ClonedCardId = $original->Id;
                $clone->ParentCardId = $improv->Id;
                $clone->Name = $original->Name;

                /** @var Action_01058 $stolenAction */
                $stolenAction = $original->getActions()[0];
                $clonedAction = clone $stolenAction;
                $clonedAction->setOwnerId($clone->Id);
                $clone->addAction($clonedAction, $world->game, false);

                $discarded = new EventCardDiscardedFromHand();
                $discarded->cardId = $clone->Id;
                $discarded->ownerId = 2;
                $world->fireOn($clone, $discarded);
                $clone->Location = 'Discard-2';

                Assert::true($clone->PendingCleanup, 'awaiting resolve');
                Assert::count(0, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'not hidden yet');

                $world->theah->queuedEvents = [];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_ACTION, $clonedAction->Id);

                // Simulate post-pay request: shell still in discard → in $this->cards.
                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $clonedAction->Id;
                $targeted->targetId = $foe->Id;
                $targeted->sourceId = $clone->Id;
                $targeted->batchId = 7;
                $world->fire($targeted);

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($foe->Id, $moves[0]->cardId, 'foe moves');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'stolen resolves');

                $world->theah->queuedEvents = [];
                $resolved = new EventActionResolved();
                $resolved->playerId = 1;
                $world->fire($resolved);

                Assert::false($clone->PendingCleanup, 'cleaned');
                Assert::true(
                    $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class)[0]->permanentlyHide,
                    'shell hidden after resolve'
                );
            },
        ];
    }
}
