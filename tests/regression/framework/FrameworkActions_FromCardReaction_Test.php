<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\AbilityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCancelRiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericTheahReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

/**
 * FrameworkActionsTrait: the generic "answer a card's prompt" entry points
 * (actFromCard*), reaction answers (actReactionForState) and paying for an in-hand Risk Reaction.
 */
class FrameworkActions_FromCardReaction_Test extends TestCase
{
    public function name(): string
    {
        return 'FrameworkActionsTrait: from-card & reaction';
    }

    /**
     * A card carrying one Action, with the transition globals pointing at it.
     *
     * @return array{0:FrameworkActionsGame,1:AbilityCharacter,2:GenericCharacterAction}
     */
    private function promptScene(): array
    {
        $game = new FrameworkActionsGame();
        $owner = new AbilityCharacter('Prompting card');
        $owner->addAction(new GenericCharacterAction(), $game, false);
        $game->place($owner, Game::LOCATION_CITY_DOCKS, 1);
        $action = $owner->getActions()[0];
        $game->globals->set(Game::TRANSITION_SOURCE_ID, $owner->Id);
        $game->globals->set(Game::TRANSITION_INTERNAL_ID, $action->Id);
        $game->gamestate->currentMainStateId = 123;
        $game->gamestate->currentMainStateName = 'promptState';
        return [$game, $owner, $action];
    }

    /**
     * A Risk in player 1's hand with a Reaction, with the transition globals pointing at it.
     *
     * @return array{0:FrameworkActionsGame,1:GenericRisk,2:CardReaction}
     */
    private function reactionScene(bool $cancel = false, int $cost = 2): array
    {
        $game = new FrameworkActionsGame();
        $risk = new GenericRisk('Parry!', $cost);
        $risk->addReaction($cancel ? new GenericCancelRiskReaction() : new GenericRiskReaction(), $game, false);
        $game->place($risk, Game::LOCATION_HAND, 1);
        $reaction = $risk->getReactions()[0];
        $game->globals->set(Game::TRANSITION_SOURCE_ID, $risk->Id);
        $game->globals->set(Game::TRANSITION_INTERNAL_ID, $reaction->Id);
        $game->globals->set(Game::REACTION_ID, 'react');
        $game->gamestate->currentMainStateId = 456;
        return [$game, $risk, $reaction];
    }

    /** @return list<int> */
    private function hand(FrameworkActionsGame $game, int $count, bool $wealth = false): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $game->place(new GenericCharacter("Pay $i", $wealth ? ['Wealth'] : []), Game::LOCATION_HAND, 1)->Id;
        }
        return $ids;
    }

    public function tests(): array
    {
        return [
            // ---- Dispatch to the source card's ability -----------------------------------------------------

            'actFromCardPass hands the pass to the Action that owns the prompt' => function () {
                [$game, , $action] = $this->promptScene();

                $game->actFromCardPass();

                Assert::same([['pass', [123]]], $action->calls, 'dispatched with the current state');
            },

            'actFromCardRevealHand hands the request to the Action that owns the prompt' => function () {
                [$game, , $action] = $this->promptScene();

                $game->actFromCardRevealHand();

                Assert::same([['revealHand', [123, 'promptState']]], $action->calls, 'dispatched with state and state name');
            },

            'actFromCardWithId hands the chosen id to the Action' => function () {
                [$game, , $action] = $this->promptScene();

                $game->actFromCardWithId(42);

                Assert::same([['withId', [123, 'promptState', 42]]], $action->calls, 'dispatched');
            },

            'actFromCardWithIds decodes the id list and hands it to the Action' => function () {
                [$game, , $action] = $this->promptScene();

                $game->actFromCardWithIds('[4,5,6]');

                Assert::same([['withIds', [123, 'promptState', [4, 5, 6]]]], $action->calls, 'dispatched');
            },

            'actFromCardWithLocations reuses the id-list dispatch for location names' => function () {
                [$game, , $action] = $this->promptScene();

                $game->actFromCardWithLocations('["Docks","Forum"]');

                // WHY asserted: there is no per-location Action hook — locations travel through
                // actFromActionWithIds. Cards rely on that; a "fix" to a new hook would silently
                // break every Action that answers a location prompt.
                Assert::same([['withIds', [123, 'promptState', ['Docks', 'Forum']]]], $action->calls, 'dispatched as ids');
            },

            'actFromCardWithActionId hands the chosen action and its source to the Action' => function () {
                [$game, , $action] = $this->promptScene();

                $game->actFromCardWithActionId(77, 'OtherCard_Action');

                Assert::same([['withActionId', [123, 'promptState', 77, 'OtherCard_Action']]], $action->calls, 'dispatched');
            },

            'actFromCard* trusts the numeric prefix of the internal id over a stale transition source' => function () {
                [$game, $owner, $action] = $this->promptScene();
                $decoy = new AbilityCharacter('Decoy');
                $decoy->addAction(new GenericCharacterAction(), $game, false);
                $game->place($decoy, Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::TRANSITION_SOURCE_ID, $decoy->Id); // stale: the prompt belongs to $owner

                $game->actFromCardWithId(9);

                Assert::same([['withId', [123, 'promptState', 9]]], $action->calls, 'the prefix picked the real owner');
                Assert::same([], $decoy->getActions()[0]->calls, 'the stale source was not used');
            },

            'actFromCard* dispatches to the transition source when the prompt is a Theah-level one' => function () {
                [$game, $owner, $action] = $this->promptScene();
                // an internal id without a numeric prefix cannot override the source
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, $action->Id);
                $other = $game->place(new AbilityCharacter('Other'), Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::TRANSITION_SOURCE_ID, $other->Id);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, 'unprefixed');

                $game->actFromCardPass();

                Assert::same([], $action->calls, 'nothing dispatched to the owner');
            },

            'actFromCard* ignores an internal id that no ability on the card matches' => function () {
                [$game, $owner, $action] = $this->promptScene();
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, $owner->Id . '_Missing');

                $game->actFromCardWithId(1);
                $game->actFromCardPass();

                Assert::same([], $action->calls, 'nothing dispatched');
            },

            'actFromCard* refuses a missing or unknown source card' => function () {
                $entryPoints = [
                    'actFromCardPass' => fn(FrameworkActionsGame $g) => $g->actFromCardPass(),
                    'actFromCardRevealHand' => fn(FrameworkActionsGame $g) => $g->actFromCardRevealHand(),
                    'actFromCardWithId' => fn(FrameworkActionsGame $g) => $g->actFromCardWithId(1),
                    'actFromCardWithIds' => fn(FrameworkActionsGame $g) => $g->actFromCardWithIds('[1]'),
                    'actFromCardWithLocations' => fn(FrameworkActionsGame $g) => $g->actFromCardWithLocations('["Docks"]'),
                    'actFromCardWithActionId' => fn(FrameworkActionsGame $g) => $g->actFromCardWithActionId(1, 'x'),
                ];
                foreach ($entryPoints as $label => $call) {
                    $game = new FrameworkActionsGame();
                    Assert::throws(fn() => $call($game), 'Unable to process action', "$label: no source");

                    $game->globals->set(Game::TRANSITION_SOURCE_ID, 99999);
                    Assert::throws(fn() => $call($game), 'Card not found', "$label: unknown source");

                    $game->globals->set(Game::TRANSITION_SOURCE_ID, Game::THEAH_ID);
                    Assert::throws(fn() => $call($game), 'Card not found', "$label: Theah is not a card");
                }
            },

            // ---- Reaction answers -----------------------------------------------------------------------------

            'actReactionForState has the owning card perform the reaction and remembers the answer' => function () {
                [$game, $risk, $reaction] = $this->reactionScene();
                $game->globals->delete(Game::REACTION_ID);

                $game->actReactionForState('react');

                Assert::same('react', $game->globals->get(Game::REACTION_ID), 'answer remembered');
                $activated = $game->theah->queuedEvents[0] ?? null;
                Assert::instanceOf(EventReactionActivated::class, $activated, 'the reaction announces itself first');
                Assert::same(Event::HIGHEST_PRIORITY, $activated->priority, 'highest priority so it resolves before anything queued');
                Assert::same($risk->Id, $activated->sourceId, 'source');
                Assert::same('react', $activated->reactionId, 'reaction id');
                Assert::same(1, $activated->playerId, 'player');
            },

            'actReactionForState: passing or declining does not activate anything' => function () {
                foreach (['pass', 'decline'] as $answer) {
                    [$game] = $this->reactionScene();

                    $game->actReactionForState($answer);

                    Assert::count(0, $game->queued(EventReactionActivated::class), "$answer: nothing activated");
                    Assert::same($answer, $game->globals->get(Game::REACTION_ID), "$answer: still remembered");
                }
            },

            'actReactionForState routes a Theah-level reaction to the Theah reaction' => function () {
                $game = new FrameworkActionsGame();
                $reaction = new GenericTheahReaction();
                $game->theah->registerTheahReaction($reaction);
                $game->globals->set(Game::TRANSITION_SOURCE_ID, Game::THEAH_ID);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, $reaction->Id);
                $game->gamestate->currentMainStateId = 321;

                $game->actReactionForState('yes');

                Assert::same([['state' => 321, 'internalId' => 'GenericTheahReaction', 'reactionId' => 'yes']], $reaction->performed, 'performed');
                Assert::same('yes', $game->globals->get(Game::REACTION_ID), 'answer remembered');
            },

            'actReactionForState trusts the numeric prefix of the internal id over a stale transition source' => function () {
                [$game, $risk] = $this->reactionScene();
                $decoy = $game->place(new GenericRisk('Decoy'), Game::LOCATION_HAND, 1);
                $game->globals->set(Game::TRANSITION_SOURCE_ID, $decoy->Id);

                $game->actReactionForState('react');

                Assert::same($risk->Id, $game->theah->queuedEvents[0]->sourceId, 'the prefix picked the real owner');
            },

            'actReactionForState refuses a missing or unknown source card' => function () {
                $game = new FrameworkActionsGame();
                // no internal id either: must be a clean refusal, not a null-to-substr deprecation
                Assert::throws(fn() => $game->actReactionForState('react'), 'Unable to process reaction', 'no source');

                $game->globals->set(Game::TRANSITION_SOURCE_ID, 99999);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, '99999_x');
                Assert::throws(fn() => $game->actReactionForState('react'), 'Card not found', 'unknown source');
                Assert::false($game->globals->has(Game::REACTION_ID), 'answer not remembered on failure');
            },

            // ---- Paying for a Risk Reaction --------------------------------------------------------------------

            'actPayForReaction pays, plays the Risk, then queues its play and reaction in that order' => function () {
                [$game, $risk, $reaction] = $this->reactionScene(false, 2);
                $pay = $this->hand($game, 2);

                $game->actPayForReaction(json_encode($pay));

                Assert::same(
                    [
                        EventCardDiscardedFromHand::class,
                        EventCardDiscardedFromHand::class,
                        EventCardDiscardedFromHand::class,
                        EventRiskPlayed::class,
                        EventRiskReactionTriggered::class,
                    ],
                    array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents),
                    'payment, the Risk itself, then played, then the reaction'
                );
                $discards = $game->queued(EventCardDiscardedFromHand::class);
                Assert::same($pay, [$discards[0]->cardId, $discards[1]->cardId], 'payment cards first');
                Assert::same($risk->Id, $discards[2]->cardId, 'the Risk itself last');
                $triggered = $game->queued(EventRiskReactionTriggered::class)[0];
                Assert::same(1, $triggered->playerId, 'player');
                Assert::same($risk->Id, $triggered->sourceId, 'source');
                Assert::same($reaction->Id, $triggered->internalId, 'internal id');
                Assert::same('react', $triggered->reactionId, 'reaction id');
                Assert::contains('played ${card_inject_code}', $game->notify->messages[0]['message'], 'announced');
                Assert::same(['paid'], $game->gamestate->transitions, 'transition');
            },

            'actPayForReaction: a cancelling reaction is stacked so it resolves before what it cancels' => function () {
                [$game, $risk] = $this->reactionScene(true, 1);
                $pay = $this->hand($game, 1);

                $game->actPayForReaction(json_encode($pay));

                Assert::same(
                    [
                        EventRiskPlayed::class,
                        EventRiskReactionTriggered::class,
                        EventCardDiscardedFromHand::class,
                        EventCardDiscardedFromHand::class,
                    ],
                    array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents),
                    'stacked: played, reaction, then the discards'
                );
                Assert::same($risk->Id, $game->queued(EventRiskPlayed::class)[0]->riskId, 'risk');
                Assert::same(['paid'], $game->gamestate->transitions, 'transition');
            },

            'actPayForReaction applies hand-reaction discounts from cards in play' => function () {
                [$game] = $this->reactionScene(false, 2);
                $game->place(new class('Cheap tricks') extends GenericCharacter {
                    public function getReactionFromHandDiscount(Theah $theah, CardReaction $requestedReaction, array &$explanations): int
                    {
                        return 1;
                    }
                }, Game::LOCATION_CITY_FORUM, 1);

                Assert::throws(
                    fn() => $game->actPayForReaction(json_encode($this->hand($game, 2))),
                    'Cost of Card is 1. You selected 2 Wealth',
                    'full price no longer matches'
                );
                $game->actPayForReaction(json_encode($this->hand($game, 1)));
                Assert::same(['paid'], $game->gamestate->transitions, 'discounted price accepted');
            },

            'actPayForReaction rejects a payment that does not match the cost' => function () {
                [$game] = $this->reactionScene(false, 2);

                Assert::throws(
                    fn() => $game->actPayForReaction(json_encode($this->hand($game, 1))),
                    'Cost of Card is 2. You selected 1 Wealth',
                    'underpaid'
                );
                Assert::throws(fn() => $game->actPayForReaction(json_encode([99999])), 'Card #99999 not found', 'ghost card');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actPayForReaction lets a Wealth card overpay by one' => function () {
                [$game] = $this->reactionScene(false, 1);

                $game->actPayForReaction(json_encode($this->hand($game, 1, true)));

                Assert::same(['paid'], $game->gamestate->transitions, 'accepted');
            },

            'actPayForReaction refuses cards the reaction forbids as payment, and forgets the restriction afterwards' => function () {
                [$game] = $this->reactionScene(false, 1);
                $forbidden = $game->place(new GenericCharacter('Forbidden'), Game::LOCATION_HAND, 1);
                $allowed = $game->place(new GenericCharacter('Allowed'), Game::LOCATION_HAND, 1);
                $game->globals->set(Game::INVALID_PAY_CARD_IDS, [$forbidden->Id]);

                Assert::throws(fn() => $game->actPayForReaction(json_encode([$forbidden->Id])), 'Forbidden is not valid to pay', 'refused');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::true($game->globals->has(Game::INVALID_PAY_CARD_IDS), 'restriction kept after a failed attempt');

                $game->actPayForReaction(json_encode([$allowed->Id]));
                Assert::false($game->globals->has(Game::INVALID_PAY_CARD_IDS), 'restriction cleared once paid');
            },

            'actPayForReaction refuses a missing or unknown source card' => function () {
                $game = new FrameworkActionsGame();
                // no internal id either: must be a clean refusal, not a null-to-substr deprecation
                Assert::throws(fn() => $game->actPayForReaction('[]'), 'Unable to process reaction', 'no source');

                $game->globals->set(Game::TRANSITION_SOURCE_ID, 99999);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, '99999_x');
                Assert::throws(fn() => $game->actPayForReaction('[]'), 'Card not found', 'unknown source');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
