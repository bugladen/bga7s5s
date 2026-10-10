<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01105;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01154;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01154_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01105;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01154;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01154_Test extends TestCase
{
    private const DISCARD = 'Discard-1';

    public function name(): string
    {
        return 'Action_01154';
    }

    /**
     * Corpse Speak on a ready Sorcerer; playable Risk in discard.
     *
     * @return array{0:_01154,1:Action_01154,2:GenericCharacter,3:_01105}
     */
    private function scene(TestWorld $world): array
    {
        $host = $world->placeCharacter(
            new GenericCharacter('Witch', ['Sorcerer']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        /** @var _01154 $speak */
        $speak = $world->placeCard(new _01154(), Game::LOCATION_CITY_DOCKS, 1);
        $speak->AttachedToId = $host->Id;
        $host->Attachments[] = $speak->Id;
        $risk = $world->placeCard(new _01105(), self::DISCARD, 1);
        /** @var Action_01154 $action */
        $action = $speak->getActions()[0];
        return [$speak, $action, $host, $risk];
    }

    public function tests(): array
    {
        return [
            'is an AttachmentAction Sorcerer ability that targets cards' => function () {
                $action = new Action_01154();
                Assert::instanceOf(AttachmentAction::class, $action, 'AttachmentAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'ISorcererAbility');
                Assert::instanceOf(IAbilityThatTargetsCards::class, $action, 'targets cards');
            },

            'available when host is ready and discard has a playable Risk' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when host is engaged' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->scene($world);
                $host->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when discard has no Risks' => function () {
                $world = new TestWorld();
                [, $action, , $risk] = $this->scene($world);
                $risk->Location = Game::LOCATION_HAND;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty');
            },

            'trigger engages host then queues 01154 chooser' => function () {
                $world = new TestWorld();
                [$speak, $action, $host] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage cost');
                Assert::same($host->Id, $engages[0]->cardId, 'host');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('01154', $transitions[0]->transition, 'name');
                Assert::same($speak->Id, $transitions[0]->sourceId, 'source');
            },

            'args list discard Risks and their available actions' => function () {
                $world = new TestWorld();
                [, $action, $host, $risk] = $this->scene($world);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01154,
                    'highDramaPlayerTurn_01154'
                );

                Assert::same($host->Id, $args['performerId'], 'performer');
                Assert::count(1, $args['cards'], 'one risk');
                Assert::same($risk->Id, $args['cards'][0]['id'], 'Drinking Games');
                Assert::true(count($args['actions']) >= 1, 'actions');
            },

            'actWithActionId queues Sorcery start and 01154_2' => function () {
                $world = new TestWorld();
                [$speak, $action, , $risk] = $this->scene($world);
                /** @var Action_01105 $riskAction */
                $riskAction = $risk->getActions()[0];

                $action->actFromActionWithActionId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01154,
                    'highDramaPlayerTurn_01154',
                    $risk->Id,
                    $riskAction->Id
                );

                Assert::same($riskAction->Id, $world->game->globals->get(Game::CHOSEN_ACTION), 'action');
                Assert::same($risk->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'card');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'sorcery start');
                Assert::same('01154_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'step 2');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            // WHY (journal 2026-06-05-01): ActionResolved must NOT fire from stateFromAction —
            // it races inHandActionPay and breaks active-player handoff. Clone discard owns it.
            'stateFromAction clones Risk into hand without queueing ActionResolved' => function () {
                $world = new TestWorld();
                [$speak, $action, , $risk] = $this->scene($world);
                /** @var Action_01105 $riskAction */
                $riskAction = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_CARD, $risk->Id);
                $world->game->globals->set(Game::CHOSEN_ACTION, $riskAction->Id);

                $action->stateFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01154_2,
                    'highDramaPlayerTurn_01154_2'
                );

                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $risk->Location, 'original hidden');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'removed');

                $clones = array_filter(
                    $world->game->createdCardsInLocation,
                    fn($c) => $c instanceof _01154_RiskClone
                );
                Assert::count(1, $clones, 'one clone');
                /** @var _01154_RiskClone $clone */
                $clone = array_values($clones)[0];
                Assert::same(Game::LOCATION_HAND, $clone->Location, 'in hand');
                Assert::same($risk->Id, $clone->ClonedCardId, 'cloned id');
                Assert::same($speak->Id, $clone->AttachmentId, 'attachment');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToHand::class), 'added');
                Assert::true($world->game->globals->get(Game::ABNORMAL_FLOW), 'abnormal');
                Assert::same(
                    'inHandActionChoosePerformer',
                    $world->theah->queuedOfType(EventTransition::class)[0]->transition,
                    'performer chooser'
                );
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'sorcery played');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no ActionResolved yet');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay deferred');
            },

            // WHY (journal 2026-06-05-01): ActionResolved fires from RiskClone discard after
            // Corpse Speak unequips + Locker — the moment the action actually resolved.
            'RiskClone discard hides clone, restores original, lockers Corpse Speak, and resolves' => function () {
                $world = new TestWorld();
                [$speak, , $host, $risk] = $this->scene($world);
                $risk->Location = Game::LOCATION_PERMANENTLY_HIDDEN;

                /** @var _01154_RiskClone $clone */
                $clone = $world->placeCard(new _01154_RiskClone(), Game::LOCATION_HAND, 1);
                $clone->ClonedCardId = $risk->Id;
                $clone->AttachmentId = $speak->Id;

                $event = new EventCardDiscardedFromHand();
                $event->cardId = $clone->Id;
                $event->ownerId = 1;
                $world->fireOn($clone, $event);

                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $clone->Location, 'clone hidden');
                Assert::same(self::DISCARD, $risk->Location, 'original restored');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'clone removed');

                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequip');
                Assert::same($speak->Id, $unequip[0]->attachmentId, 'speak');
                Assert::same($host->Id, $unequip[0]->characterId, 'host');

                $locker = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::count(1, $locker, 'locker');
                Assert::same($speak->Id, $locker[0]->cardId, 'speak to locker');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved on discard');
            },

            // WHY: cancel path (no usable actions) must NOT queue ActionResolved — action never resolved.
            'cancel after engage en gardes host, refunds Used, grants extra action, no ActionResolved' => function () {
                $world = new TestWorld();
                [$speak, $action, $host] = $this->scene($world);
                $action->Used = true;

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01154,
                    'highDramaPlayerTurn_01154',
                    0
                );

                $engardes = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engardes, 'en garde');
                Assert::same($host->Id, $engardes[0]->cardId, 'host');
                Assert::false($action->Used, 'refunded');
                Assert::same(1, $world->game->globals->get(Game::EXTRA_ACTIONS), 'extra');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'state constants registered' => function () {
                Assert::same(401154, States::HIGH_DRAMA_PLAYER_TURN_01154, 'chooser');
                Assert::same(4011542, States::HIGH_DRAMA_PLAYER_TURN_01154_2, 'clone');
            },
        ];
    }
}
