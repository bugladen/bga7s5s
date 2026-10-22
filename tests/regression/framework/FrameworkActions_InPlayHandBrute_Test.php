<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01024;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\AbilityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLocationAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/** FrameworkActionsTrait: In-Play Actions, In-Hand (Risk) Actions and Brutes. */
class FrameworkActions_InPlayHandBrute_Test extends TestCase
{
    public function name(): string
    {
        return 'FrameworkActionsTrait: in-play, in-hand & brute';
    }

    /** @return list<int> ids of fresh hand cards for player 1 (plain, or Wealth-trait when $wealth) */
    private function hand(FrameworkActionsGame $game, int $count, bool $wealth = false, array $traits = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $game->place(new GenericCharacter("Pay $i", array_merge($traits, $wealth ? ['Wealth'] : [])), Game::LOCATION_HAND, 1)->Id;
        }
        return $ids;
    }

    /**
     * A character in the city carrying one Action.
     *
     * @return array{0:FrameworkActionsGame,1:AbilityCharacter,2:GenericCharacterAction}
     */
    private function inPlayScene(bool $requiresPerformer = false): array
    {
        $game = new FrameworkActionsGame();
        $owner = new AbilityCharacter('Actor');
        $owner->addAction(new GenericCharacterAction($requiresPerformer), $game, false);
        $game->place($owner, Game::LOCATION_CITY_DOCKS, 1);
        return [$game, $owner, $owner->getActions()[0]];
    }

    /**
     * A Risk in player 1's hand carrying one in-hand Action.
     *
     * @return array{0:FrameworkActionsGame,1:GenericRisk,2:GenericRiskAction}
     */
    private function inHandScene(int $cost = 2, bool $requiresPerformer = false): array
    {
        $game = new FrameworkActionsGame();
        $risk = new GenericRisk('Test Risk', $cost);
        $risk->addAction(new GenericRiskAction($requiresPerformer), $game, false);
        $game->place($risk, Game::LOCATION_HAND, 1);
        $action = $risk->getActions()[0];
        $game->globals->set(Game::CHOSEN_ACTION, $action->Id);
        return [$game, $risk, $action];
    }

    public function tests(): array
    {
        return [
            // ---- In-Play Actions -----------------------------------------------------------------------

            'actHighDramaChooseInPlayActionStart proceeds when the player has an available Action in play' => function () {
                [$game] = $this->inPlayScene();

                $game->actHighDramaChooseInPlayActionStart();

                Assert::same(['inPlayActionStart'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaChooseInPlayActionStart is refused when every Action is used or unavailable' => function () {
                [$game, , $action] = $this->inPlayScene();
                $action->available = false;
                Assert::throws(fn() => $game->actHighDramaChooseInPlayActionStart(), 'In-Play Action is not allowed', 'unavailable');

                $action->available = true;
                $action->Used = true;
                Assert::throws(fn() => $game->actHighDramaChooseInPlayActionStart(), 'In-Play Action is not allowed', 'used');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaChooseInPlayActionStart does not count Actions of cards still in hand' => function () {
                [$game, $risk, $action] = $this->inHandScene();

                Assert::throws(fn() => $game->actHighDramaChooseInPlayActionStart(), 'In-Play Action is not allowed', 'hand Action is not in play');
            },

            'actHighDramaInPlayActionChosen records the Action and defaults the performer to its owner' => function () {
                [$game, $owner, $action] = $this->inPlayScene();

                $game->actHighDramaInPlayActionChosen($action->Id);

                Assert::same($action->Id, $game->globals->get(Game::CHOSEN_ACTION), 'chosen action');
                Assert::same($action->Id, $game->globals->get(Game::TRANSITION_INTERNAL_ID), 'transition id');
                Assert::same($owner->Id, $game->globals->get(Game::CHOSEN_PERFORMER), 'owner is the performer');
                Assert::same(['actionChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaInPlayActionChosen works for a location Action and leaves the performer unset' => function () {
                $game = new FrameworkActionsGame();
                $action = new GenericLocationAction($game);
                $game->theah->registerTheahAction($action);

                $game->actHighDramaInPlayActionChosen($action->Id);

                Assert::same($action->Id, $game->globals->get(Game::CHOSEN_ACTION), 'chosen action');
                Assert::false($game->globals->has(Game::CHOSEN_PERFORMER), 'no performer');
                Assert::same(['actionChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaInPlayActionChosen refuses an unknown or unavailable Action' => function () {
                [$game, , $action] = $this->inPlayScene();

                Assert::throws(fn() => $game->actHighDramaInPlayActionChosen('nope'), 'Action not found', 'unknown');

                $action->available = false;
                Assert::throws(fn() => $game->actHighDramaInPlayActionChosen($action->Id), 'not available to player', 'unavailable');

                [$game, , $action] = $this->inPlayScene();
                $game->activePlayerId = 2; // the Action belongs to player 1's character
                Assert::throws(fn() => $game->actHighDramaInPlayActionChosen($action->Id), 'not available to player', "opponent's Action");
                Assert::same([], $game->gamestate->transitions, 'no transition');
                Assert::false($game->globals->has(Game::CHOSEN_ACTION), 'nothing recorded');
            },

            'actHighDramaInPlayActionChosen honours the Extra Action performer lock for character Actions' => function () {
                [$game, , $action] = $this->inPlayScene();
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 4242);

                Assert::throws(fn() => $game->actHighDramaInPlayActionChosen($action->Id), 'same character', 'locked elsewhere');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaInPlayActionConfirm announces and spends a card Action and resets the pass count' => function () {
                [$game, $owner, $action] = $this->inPlayScene();
                $game->globals->set(Game::CHOSEN_ACTION, $action->Id);
                $game->globals->set(Game::PASS_COUNT, 2);

                $game->actHighDramaInPlayActionConfirm();

                Assert::true($action->Used, 'Action spent');
                $activated = $game->queued(EventActionActivated::class);
                Assert::count(1, $activated, 'announcement event');
                Assert::same($owner->Id, $activated[0]->sourceId, 'source is the owning card');
                Assert::same($action->Id, $activated[0]->actionId, 'action id');
                $used = $game->queued(EventActionUsed::class);
                Assert::count(1, $used, 'used event');
                Assert::same(0, $game->globals->get(Game::PASS_COUNT), 'pass count reset');
                Assert::same(['confirmed'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaInPlayActionConfirm marks a location Action used by the player, not the Action itself' => function () {
                $game = new FrameworkActionsGame();
                $action = new GenericLocationAction($game);
                $game->theah->registerTheahAction($action);
                $game->globals->set(Game::CHOSEN_ACTION, $action->Id);

                $game->actHighDramaInPlayActionConfirm();

                Assert::same([1], $action->playersUsed(), 'used by player 1');
                Assert::count(1, $game->queued(EventActionActivated::class), 'announcement event');
                Assert::count(0, $game->queued(EventActionUsed::class), 'no card-style used event');
                Assert::same(['confirmed'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaInPlayActionConfirm refuses a missing or no-longer-available Action' => function () {
                [$game, , $action] = $this->inPlayScene();

                Assert::throws(fn() => $game->actHighDramaInPlayActionConfirm(), 'Action not found', 'nothing chosen');

                $game->globals->set(Game::CHOSEN_ACTION, $action->Id);
                $action->Used = true;
                Assert::throws(fn() => $game->actHighDramaInPlayActionConfirm(), 'not available to player', 'already used');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaInPlayActionPerformerChosen records the performer' => function () {
                [$game, $owner] = $this->inPlayScene();
                $other = $game->place(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 1);

                $game->actHighDramaInPlayActionPerformerChosen($other->Id);

                Assert::same($other->Id, $game->globals->get(Game::CHOSEN_PERFORMER), 'recorded');
                Assert::same(['inPlayActionPerformerChosen'], $game->gamestate->transitions, 'transition');
                Assert::count(0, $game->queued(EventTransition::class), 'no resume without a remembered entry');
            },

            // WHY: First Confirm still has EventActionTriggered queued by dispatch.
            // Re-queueing the previous action's entry here would skip that trigger.
            'actHighDramaInPlayActionPerformerChosen leaves the pending trigger alone' => function () {
                [$game] = $this->inPlayScene();
                $other = $game->place(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::IN_PLAY_ACTION_TRIGGER_PENDING, 1);
                $game->globals->set(Game::IN_PLAY_ACTION_ENTRY_TRANSITION, '01148');

                $game->actHighDramaInPlayActionPerformerChosen($other->Id);

                Assert::false($game->globals->has(Game::IN_PLAY_ACTION_TRIGGER_PENDING), 'pending consumed');
                Assert::count(0, $game->queued(EventTransition::class), 'stale entry not replayed');
                Assert::same(['inPlayActionPerformerChosen'], $game->gamestate->transitions, 'transition');
            },

            // WHY: Back from highDramaPhase01149 (and the other performer UIs) returns
            // to choose-performer after the trigger was consumed. Confirm must reopen
            // that UI. An empty queue endOfEvents-advances the turn and the action
            // resolves as a no-op.
            'actHighDramaInPlayActionPerformerChosen reopens the action after Back' => function () {
                [$game, $owner] = $this->inPlayScene();
                $other = $game->place(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::IN_PLAY_ACTION_ENTRY_TRANSITION, '01149');
                $game->globals->set(Game::IN_PLAY_ACTION_ENTRY_PLAYER_ID, 1);
                $game->globals->set(Game::TRANSITION_SOURCE_ID, $owner->Id);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, 'action-01149');

                $game->actHighDramaInPlayActionPerformerChosen($other->Id);

                $transitions = $game->queued(EventTransition::class);
                Assert::count(1, $transitions, 'entry requeued');
                Assert::same('01149', $transitions[0]->transition, 'midnight shipment');
                Assert::same(1, $transitions[0]->playerId, 'player');
                Assert::same($owner->Id, $transitions[0]->sourceId, 'source');
                Assert::same('action-01149', $transitions[0]->internalId, 'internal');
                Assert::same($other->Id, $game->globals->get(Game::CHOSEN_PERFORMER), 'new performer');
                Assert::same(['inPlayActionPerformerChosen'], $game->gamestate->transitions, 'events will apply it');
            },

            'actHighDramaInPlayActionPerformerChosen refuses an unknown performer and honours the lock' => function () {
                [$game, $owner] = $this->inPlayScene();

                Assert::throws(fn() => $game->actHighDramaInPlayActionPerformerChosen(99999), 'Performer not found', 'unknown');

                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 4242);
                Assert::throws(fn() => $game->actHighDramaInPlayActionPerformerChosen($owner->Id), 'same character', 'locked elsewhere');
                Assert::false($game->globals->has(Game::CHOSEN_PERFORMER), 'nothing recorded');
            },

            // ---- In-Hand Actions ------------------------------------------------------------------------------

            'actHighDramaChooseInHandActionStart proceeds when a Risk in hand has an available Action' => function () {
                [$game] = $this->inHandScene();

                $game->actHighDramaChooseInHandActionStart();

                Assert::same(['inHandActionStart'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaChooseInHandActionStart is refused with no usable in-hand Action' => function () {
                [$game, , $action] = $this->inHandScene();
                $action->available = false;
                Assert::throws(fn() => $game->actHighDramaChooseInHandActionStart(), 'In-Hand Action is not allowed', 'unavailable');

                $game = new FrameworkActionsGame();
                Assert::throws(fn() => $game->actHighDramaChooseInHandActionStart(), 'In-Hand Action is not allowed', 'empty hand');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaInHandActionChosen without a performer requirement goes straight to payment' => function () {
                [$game, $risk, $action] = $this->inHandScene();
                $game->globals->set(Game::CHOSEN_ACTION, '');

                $game->actHighDramaInHandActionChosen($action->Id);

                Assert::same($action->Id, $game->globals->get(Game::CHOSEN_ACTION), 'chosen action');
                Assert::same($action->Id, $game->globals->get(Game::TRANSITION_INTERNAL_ID), 'transition id');
                $pay = $game->queued(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay prompt');
                Assert::same($risk->Id, $pay[0]->cardId, 'for the Risk');
                Assert::same(Game::PAY_STATE_IN_HAND_ACTION, $pay[0]->payStateType, 'in-hand payment');
                Assert::same(['inHandActionChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaInHandActionChosen with a performer requirement asks for the performer first' => function () {
                [$game, , $action] = $this->inHandScene(2, true);

                $game->actHighDramaInHandActionChosen($action->Id);

                Assert::count(0, $game->queued(EventEnteringPayState::class), 'no pay prompt yet');
                Assert::same(['requiresPerformerSelected'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaInHandActionChosen refuses an unknown or unavailable Action' => function () {
                [$game, , $action] = $this->inHandScene();

                Assert::throws(fn() => $game->actHighDramaInHandActionChosen('nope'), 'Action not found', 'unknown');

                $action->available = false;
                Assert::throws(fn() => $game->actHighDramaInHandActionChosen($action->Id), 'not available to player', 'unavailable');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaInHandActionChosen under an Extra Action lock picks the locked performer' => function () {
                [$game, , $action] = $this->inHandScene();
                $locked = $game->place(new GenericCharacter('Locked in'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, $locked->Id);

                $game->actHighDramaInHandActionChosen($action->Id);

                Assert::same($locked->Id, $game->globals->get(Game::CHOSEN_PERFORMER), 'locked performer is used');
                Assert::same(['inHandActionChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaInHandActionChosen under an Extra Action lock refuses an Action the locked character cannot perform' => function () {
                [$game, , $action] = $this->inHandScene();
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 4242); // not a performer for the Risk

                Assert::throws(fn() => $game->actHighDramaInHandActionChosen($action->Id), 'designated character', 'refused');
                Assert::same([], $game->gamestate->transitions, 'no transition');
                Assert::count(0, $game->queued(EventEnteringPayState::class), 'no pay prompt');
            },

            'actHighDramaInHandActionPerformerChosen records the performer and queues the payment prompt' => function () {
                [$game, $risk, $action] = $this->inHandScene(2, true);
                $performer = $game->place(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);

                $game->actHighDramaInHandActionPerformerChosen($performer->Id);

                Assert::same($performer->Id, $game->globals->get(Game::CHOSEN_PERFORMER), 'recorded');
                $pay = $game->queued(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay prompt');
                Assert::same($risk->Id, $pay[0]->cardId, 'for the Risk');
                Assert::same(['inHandActionPerformerChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaInHandActionPerformerChosen honours the Extra Action performer lock' => function () {
                [$game, , ] = $this->inHandScene(2, true);
                $performer = $game->place(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 4242);

                Assert::throws(fn() => $game->actHighDramaInHandActionPerformerChosen($performer->Id), 'same character', 'locked elsewhere');
                Assert::count(0, $game->queued(EventEnteringPayState::class), 'no pay prompt');
            },

            'actPayForInHandAction pays, plays the Risk and queues its Action in order' => function () {
                [$game, $risk, $action] = $this->inHandScene(2, true);
                $performer = $game->place(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::PASS_COUNT, 3);
                $pay = $this->hand($game, 2);

                $game->actPayForInHandAction(json_encode($pay));

                Assert::same(Game::LOCATION_PURGATORY, $risk->Location, 'the Risk is parked while its Action resolves');
                Assert::true($action->Used, 'Action spent');
                Assert::same(0, $game->globals->get(Game::PASS_COUNT), 'pass count reset');
                Assert::same(['actionPaidFor'], $game->gamestate->transitions, 'transition');

                $types = array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents);
                $firstPayment = array_search(EventCardDiscardedFromHand::class, $types, true);
                $riskPlayed = array_search(EventRiskPlayed::class, $types, true);
                $triggered = array_search(EventActionTriggered::class, $types, true);
                $lastDiscard = array_keys($types, EventCardDiscardedFromHand::class, true);
                Assert::true($firstPayment < $riskPlayed, 'payment before the Risk is played');
                Assert::true($riskPlayed < $triggered, 'Risk played before its Action triggers');
                Assert::true($triggered < end($lastDiscard), 'the Risk itself is discarded last');
                Assert::count(3, $game->queued(EventCardDiscardedFromHand::class), '2 payment cards + the Risk');

                $played = $game->queued(EventRiskPlayed::class)[0];
                Assert::same(1, $played->playerId, 'player');
                Assert::same($risk->Id, $played->riskId, 'risk');
                $trigger = $game->queued(EventActionTriggered::class)[0];
                Assert::same($performer->Id, $trigger->performerId, 'performer');
                Assert::same($risk->Id, $trigger->sourceId, 'source');
                Assert::same($action->Id, $trigger->actionId, 'action');
                Assert::count(1, $game->theah->checkedEvents, 'only the trigger is offered to the cards first');
                Assert::count(1, $game->queued(EventActionActivated::class), 'announced');
            },

            'actPayForInHandAction passes performer 0 when the Action needs no performer' => function () {
                [$game, , ] = $this->inHandScene(0, false);
                $game->globals->set(Game::CHOSEN_PERFORMER, 4242); // stale value from an earlier action

                $game->actPayForInHandAction('[]');

                Assert::same(0, $game->queued(EventActionTriggered::class)[0]->performerId, 'no performer');
            },

            'actPayForInHandAction applies the discount, announces it, and skips the notice without one' => function () {
                [$game, , ] = $this->inHandScene(3);
                $game->globals->set(Game::DISCOUNT, 1);
                $game->globals->set(Game::DISCOUNT_EXPLAINATIONS, 'Haggled.');

                $game->actPayForInHandAction(json_encode($this->hand($game, 2)));

                $messages = array_column($game->notify->messages, 'message');
                Assert::count(1, array_filter($messages, fn($m) => str_contains($m, 'discount of')), 'discount announced');
                Assert::count(1, array_filter($messages, fn($m) => str_contains($m, 'played ${card_inject_code}')), 'play announced');

                [$game, , ] = $this->inHandScene(1);
                $game->actPayForInHandAction(json_encode($this->hand($game, 1)));
                $messages = array_column($game->notify->messages, 'message');
                Assert::count(0, array_filter($messages, fn($m) => str_contains($m, 'discount of')), 'no discount, no notice');
            },

            'actPayForInHandAction rejects a payment that does not match the cost' => function () {
                [$game, $risk, ] = $this->inHandScene(2);

                Assert::throws(
                    fn() => $game->actPayForInHandAction(json_encode($this->hand($game, 1))),
                    'Cost of Card is 2. You selected 1 Wealth',
                    'underpaid'
                );
                Assert::throws(fn() => $game->actPayForInHandAction(json_encode([99999])), 'Card #99999 not found', 'ghost card');
                Assert::same(Game::LOCATION_HAND, $risk->Location, 'the Risk stays in hand');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actPayForInHandAction lets a Wealth card overpay by one' => function () {
                [$game, , ] = $this->inHandScene(1);

                $game->actPayForInHandAction(json_encode($this->hand($game, 1, true)));

                Assert::same(['actionPaidFor'], $game->gamestate->transitions, 'accepted');
            },

            'actPayForInHandAction refuses a missing Action, including one whose Risk left the active hand' => function () {
                [$game, $risk, ] = $this->inHandScene(0);

                // WHY "not found" rather than "Risk is not in Player's Hand": getInHandActionById only
                // searches the active player's hand, so that later sanity check is a defensive backstop
                // that the lookup makes unreachable through this entry point.
                $risk->ControllerId = 2; // now sitting in the opponent's hand
                Assert::throws(fn() => $game->actPayForInHandAction('[]'), 'In-Hand Action not found', 'opponent hand');

                $game->globals->set(Game::CHOSEN_ACTION, 'nope');
                Assert::throws(fn() => $game->actPayForInHandAction('[]'), 'In-Hand Action not found', 'missing');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
            },

            'actPayForInHandAction: Thugs cannot pay for Bravos' => function () {
                $game = new FrameworkActionsGame();
                $bravos = new class extends _01024 {
                    public function __construct()
                    {
                        parent::__construct();
                        // WHY: the printed Leader Action has heavy requirements; the Thug rule only needs *an* in-hand Action.
                        $this->Actions = [new GenericRiskAction()];
                    }
                };
                $game->place($bravos, Game::LOCATION_HAND, 1);
                $game->globals->set(Game::CHOSEN_ACTION, $bravos->getActions()[0]->Id);
                $thug = $game->place(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_HAND, 1);

                Assert::throws(fn() => $game->actPayForInHandAction(json_encode([$thug->Id])), 'Thug cannot be used to pay for Bravos', 'thug refused');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');

                $plain = $game->place(new GenericCharacter('Plain'), Game::LOCATION_HAND, 1);
                $game->actPayForInHandAction(json_encode([$plain->Id]));
                Assert::same(['actionPaidFor'], $game->gamestate->transitions, 'a non-Thug pays fine');
            },

            // ---- Brutes ---------------------------------------------------------------------------------------

            'actHighDramaChooseBruteStart proceeds when a Brute is in hand' => function () {
                $game = new FrameworkActionsGame();
                $game->place(new GenericCharacter('Bruiser', ['Brute']), Game::LOCATION_HAND, 1);

                $game->actHighDramaChooseBruteStart();

                Assert::same(['bruteStart'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaChooseBruteStart is refused without a playable Brute in hand' => function () {
                $game = new FrameworkActionsGame();
                $game->place(new GenericCharacter('Not a brute'), Game::LOCATION_HAND, 1);
                $game->place(new GenericCharacter('On the table', ['Brute']), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new class('Cannot be played', ['Brute']) extends GenericCharacter {
                    public function canBePlayedAsBruteFromHand(): bool
                    {
                        return false;
                    }
                }, Game::LOCATION_HAND, 1);
                $game->place(new GenericCharacter('Their brute', ['Brute']), Game::LOCATION_HAND, 2);

                Assert::throws(fn() => $game->actHighDramaChooseBruteStart(), 'Brute is not allowed', 'refused');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaBruteActionBruteChosen records the Brute, its discount, and queues the payment prompt' => function () {
                $game = new FrameworkActionsGame();
                $brute = $game->place(new GenericCharacter('Bruiser', ['Brute']), Game::LOCATION_HAND, 1);
                // a card in play that discounts Brutes
                $game->place(new class('Union Boss') extends GenericCharacter {
                    public function getPlayBruteDiscount(\Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah $theah, Character $brute): int
                    {
                        return 1;
                    }
                }, Game::LOCATION_CITY_FORUM, 1);

                $game->actHighDramaBruteActionBruteChosen($brute->Id);

                Assert::same(1, $game->globals->get(Game::DISCOUNT), 'discount from the world');
                Assert::same($brute->Id, $game->globals->get(Game::CHOSEN_CARD), 'chosen card');
                $pay = $game->queued(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay prompt');
                Assert::same($brute->Id, $pay[0]->cardId, 'for the Brute');
                Assert::same(Game::PAY_STATE_PLAY_BRUTE, $pay[0]->payStateType, 'brute payment');
                Assert::same(['bruteChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaBruteActionBruteChosen refuses a card that is not in the active hand' => function () {
                $game = new FrameworkActionsGame();
                $table = $game->place(new GenericCharacter('On the table', ['Brute']), Game::LOCATION_CITY_DOCKS, 1);
                $theirs = $game->place(new GenericCharacter('Their brute', ['Brute']), Game::LOCATION_HAND, 2);

                Assert::throws(fn() => $game->actHighDramaBruteActionBruteChosen($table->Id), "Brute is not in Player's Hand", 'on the table');
                Assert::throws(fn() => $game->actHighDramaBruteActionBruteChosen($theirs->Id), "Brute is not in Player's Hand", 'opponent hand');
                Assert::throws(fn() => $game->actHighDramaBruteActionBruteChosen(99999), "Brute is not in Player's Hand", 'unknown');
                Assert::false($game->globals->has(Game::CHOSEN_CARD), 'nothing recorded');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
            },

            'actPayForBrute pays, musters the Brute Home and resolves the action' => function () {
                $game = new FrameworkActionsGame();
                $brute = $game->place(new GenericCityCharacter('Bruiser', false, ['Brute']), Game::LOCATION_HAND, 1);
                $brute->WealthCost = 2;
                $game->globals->set(Game::CHOSEN_CARD, $brute->Id);
                $game->globals->set(Game::DISCOUNT, 0);
                $game->globals->set(Game::PASS_COUNT, 2);
                $pay = $this->hand($game, 2);

                $game->actPayForBrute(json_encode($pay));

                Assert::same([
                    EventCardDiscardedFromHand::class,
                    EventCardDiscardedFromHand::class,
                    EventCharacterMustered::class,
                    EventActionResolved::class,
                ], array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents), 'pay, muster, resolve');
                $muster = $game->queued(EventCharacterMustered::class)[0];
                Assert::same(1, $muster->playerId, 'player');
                Assert::same($brute->Id, $muster->characterId, 'brute');
                Assert::same(Game::LOCATION_PLAYER_HOME, $muster->location, 'mustered Home');
                Assert::same(0, $game->globals->get(Game::PASS_COUNT), 'pass count reset');
                Assert::same(['brutePaidFor'], $game->gamestate->transitions, 'transition');
            },

            'actPayForBrute applies the discount and floors the cost at zero' => function () {
                $game = new FrameworkActionsGame();
                $brute = $game->place(new GenericCityCharacter('Bruiser', false, ['Brute']), Game::LOCATION_HAND, 1);
                $brute->WealthCost = 2;
                $game->globals->set(Game::CHOSEN_CARD, $brute->Id);

                $game->globals->set(Game::DISCOUNT, 1);
                $game->actPayForBrute(json_encode($this->hand($game, 1)));
                Assert::count(1, $game->queued(EventCharacterMustered::class), 'cost 2 - 1 = 1 card');

                $game->globals->set(Game::DISCOUNT, 5);
                $game->actPayForBrute('[]');
                Assert::count(2, $game->queued(EventCharacterMustered::class), 'floored: free');
            },

            'actPayForBrute rejects a payment that does not match the cost' => function () {
                $game = new FrameworkActionsGame();
                $brute = $game->place(new GenericCityCharacter('Bruiser', false, ['Brute']), Game::LOCATION_HAND, 1);
                $brute->WealthCost = 2;
                $game->globals->set(Game::CHOSEN_CARD, $brute->Id);
                $game->globals->set(Game::DISCOUNT, 0);

                Assert::throws(fn() => $game->actPayForBrute(json_encode($this->hand($game, 1))), 'Cost of Brute is 2. You selected 1 Wealth', 'underpaid');
                Assert::throws(fn() => $game->actPayForBrute(json_encode([99999])), 'Card #99999 not found', 'ghost card');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actPayForBrute refuses a Brute that left the active hand' => function () {
                $game = new FrameworkActionsGame();
                $brute = $game->place(new GenericCharacter('Bruiser', ['Brute']), Game::LOCATION_HAND, 1);
                $game->globals->set(Game::CHOSEN_CARD, $brute->Id);
                $game->globals->set(Game::DISCOUNT, 0);
                $brute->ControllerId = 2;

                Assert::throws(fn() => $game->actPayForBrute('[]'), "Brute is not in Player's Hand", 'refused');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
