<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardWhenRevealedEffect;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhasePlayerPassed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/**
 * FrameworkActionsTrait: pass / back / multi-state acknowledgements, deck + scheme picks,
 * Scheme renown selection, Dusk discards, and the "which reaction next" choosers.
 */
class FrameworkActions_Basics_Test extends TestCase
{
    public function name(): string
    {
        return 'FrameworkActionsTrait: basics (pass, back, deck, planning, reaction choice)';
    }

    /** A queued "reaction" transition, as Theah queues them when several cards can react. */
    private function reactionTransition(FrameworkActionsGame $game, int $playerId, string $internalId, ?int $priority = null): EventTransition
    {
        $event = EventFactory::createTransitionEvent($playerId, 100 + $playerId, 'reaction', $internalId);
        $event->priority = $priority ?? Event::REACTION_PRIORITY;
        $game->theah->queueEvent($event);
        return $event;
    }

    /** An in-hand Risk whose Reaction transition is queued for choose-next. */
    private function queuedHandRisk(FrameworkActionsGame $game, int $playerId, string $name): EventTransition
    {
        $risk = new GenericRisk($name);
        $risk->addReaction(new GenericRiskReaction(), $game, false);
        $game->place($risk, Game::LOCATION_HAND, $playerId);
        $reaction = $risk->getReactions()[0];
        $event = EventFactory::createTransitionEvent($playerId, $risk->Id, 'reaction', $reaction->Id);
        $event->priority = Event::REACTION_PRIORITY;
        $game->theah->queueEvent($event);
        return $event;
    }

    public function tests(): array
    {
        return [
            // ---- actPass / actPassWithPass ---------------------------------------------------

            'actPass announces the pass and takes the default (empty) transition' => function () {
                $game = new FrameworkActionsGame();
                $game->activePlayerId = 2;

                $game->actPass();

                Assert::count(1, $game->notify->messages, 'one notification');
                Assert::same('${player_name} passes.', $game->notify->messages[0]['message'], 'message');
                Assert::same('Player Two', $game->notify->messages[0]['args']['player_name'], 'names the active player');
                Assert::same([''], $game->gamestate->transitions, 'default transition');
            },

            'actPass honours an explicit transition name' => function () {
                $game = new FrameworkActionsGame();
                $game->actPass('somewhere');
                Assert::same(['somewhere'], $game->gamestate->transitions, 'named transition');
            },

            'actPassWithPass is actPass on the "pass" transition' => function () {
                $game = new FrameworkActionsGame();
                $game->actPassWithPass();
                Assert::same(['pass'], $game->gamestate->transitions, 'pass transition');
                Assert::same('${player_name} passes.', $game->notify->messages[0]['message'], 'announced');
            },

            // ---- actHighDramaPass ----------------------------------------------------------

            'actHighDramaPass queues PlayerPassed + ActionResolved, bumps the pass count and carries on' => function () {
                $game = new FrameworkActionsGame();
                $game->activePlayerId = 1;

                $game->actHighDramaPass();

                $passed = $game->queued(EventHighDramaPhasePlayerPassed::class);
                Assert::count(1, $passed, 'PlayerPassed queued');
                Assert::same(1, $passed[0]->playerId, 'passing player');
                $resolved = $game->queued(EventActionResolved::class);
                Assert::count(1, $resolved, 'ActionResolved queued');
                Assert::same(1, $resolved[0]->playerId, 'resolved for passing player');
                Assert::same(1, $game->globals->get(Game::PASS_COUNT), 'pass count');
                Assert::same(['pass'], $game->gamestate->transitions, 'next player gets a turn');
            },

            'actHighDramaPass ends High Drama once every player has passed in a row' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::PASS_COUNT, 1); // player 2 is the second consecutive pass

                $game->actHighDramaPass();

                Assert::same(2, $game->globals->get(Game::PASS_COUNT), 'pass count');
                Assert::same(['end'], $game->gamestate->transitions, 'phase ends');
            },

            'actHighDramaPass uses the real player count, not a hardcoded two' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::PLAYER_COUNT, 3);
                $game->globals->set(Game::PASS_COUNT, 1);

                $game->actHighDramaPass();
                Assert::same(['pass'], $game->gamestate->transitions, '2 of 3 passes: continue');

                $game->gamestate->transitions = [];
                $game->actHighDramaPass();
                Assert::same(['end'], $game->gamestate->transitions, '3 of 3 passes: end');
            },

            'actHighDramaPass is allowed while an Extra Action performer lock is active' => function () {
                // WHY: the lock says WHO acts, not WHETHER they must act (Bloody Entrance follow-up is optional).
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 99);

                $game->actHighDramaPass();

                Assert::same(['pass'], $game->gamestate->transitions, 'pass accepted');
                Assert::same(99, $game->globals->get(Game::EXTRA_ACTION_PERFORMER), 'lock left for stNextPlayer to clear');
            },

            // ---- actBack / actBackWithTransition / actSimpleTransition / actMultipleOk -----------------

            'actBack takes the back transition from an ordinary state' => function () {
                $game = new FrameworkActionsGame();
                $game->gamestate->currentMainStateName = 'highDramaMoveActionChoosePerformer';
                $game->actBack();
                Assert::same(['back'], $game->gamestate->transitions, 'back');
            },

            'actBack is refused at the challenge target picker after engaging an attachment (No More Words)' => function () {
                $game = new FrameworkActionsGame();
                $game->gamestate->currentMainStateName = 'highDramaChallengeActionChooseTarget';
                $game->globals->set(Game::CHALLENGE_TYPE, Game::NO_MORE_WORDS_CHALLENGE_TYPE);

                Assert::throws(fn() => $game->actBack(), 'cannot go back after engaging an attachment', 'blocked');
                Assert::same([], $game->gamestate->transitions, 'no transition taken');
            },

            'actBack still works at the challenge target picker for a normal challenge' => function () {
                $game = new FrameworkActionsGame();
                $game->gamestate->currentMainStateName = 'highDramaChallengeActionChooseTarget';
                $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $game->actBack();
                Assert::same(['back'], $game->gamestate->transitions, 'back');
            },

            'actBack is refused at technique activation after Valeri already moved' => function () {
                $game = new FrameworkActionsGame();
                $game->gamestate->currentMainStateName = 'highDramaChallengeActionActivateTechnique';
                $game->globals->set(Game::CHALLENGE_TYPE, Game::VALERI_CHALLENGE_TYPE);

                Assert::throws(fn() => $game->actBack(), 'cannot go back after moving', 'blocked');
                Assert::same([], $game->gamestate->transitions, 'no transition taken');
            },

            'actBack is refused at technique activation for Move Along (it never used the generic target picker)' => function () {
                $game = new FrameworkActionsGame();
                $game->gamestate->currentMainStateName = 'highDramaChallengeActionActivateTechnique';
                $game->globals->set(Game::CHALLENGE_TYPE, Game::MOVE_ALONG_CHALLENGE_TYPE);

                Assert::throws(fn() => $game->actBack(), 'cannot go back after choosing the challenge target', 'blocked');
                Assert::same([], $game->gamestate->transitions, 'no transition taken');
            },

            'actBack blocks are state-specific: the same challenge types may go back from other states' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::CHALLENGE_TYPE, Game::VALERI_CHALLENGE_TYPE);
                $game->gamestate->currentMainStateName = 'highDramaChallengeActionChooseTarget';
                $game->actBack();

                $game->globals->set(Game::CHALLENGE_TYPE, Game::NO_MORE_WORDS_CHALLENGE_TYPE);
                $game->gamestate->currentMainStateName = 'highDramaChallengeActionActivateTechnique';
                $game->actBack();

                Assert::same(['back', 'back'], $game->gamestate->transitions, 'both allowed');
            },

            'actBackWithTransition and actSimpleTransition forward the transition name verbatim' => function () {
                $game = new FrameworkActionsGame();
                $game->actBackWithTransition('cancelled');
                $game->actSimpleTransition('onward');
                Assert::same(['cancelled', 'onward'], $game->gamestate->transitions, 'transitions');
            },

            'actMultipleOk releases the current player on the multipleOk transition' => function () {
                $game = new FrameworkActionsGame();
                $game->currentPlayerId = 2;

                $game->actMultipleOk();

                Assert::same([['playerId' => 2, 'transition' => 'multipleOk']], $game->gamestate->nonMultiactive, 'released');
                Assert::same([], $game->gamestate->transitions, 'no direct transition (multiactive state)');
            },

            // ---- actPickDeck --------------------------------------------------------------------

            'actPickDeck accepts a starter deck: stores it on the player row and releases the player' => function () {
                $game = new FrameworkActionsGame();
                $game->currentPlayerId = 1;

                $game->actPickDeck('starter', 'SDCastille', '');

                $private = array_values(array_filter($game->notify->messages, fn($m) => $m['channel'] === 'player:1'));
                Assert::count(1, $private, 'one private confirmation');
                Assert::same('Core Castille Starter Deck', $private[0]['args']['deck_name'], 'names the deck');

                $updates = $game->queriesMatching('UPDATE player SET deck_source');
                Assert::count(1, $updates, 'deck stored once');
                Assert::contains("WHERE player_id='1'", $updates[0], 'for this player');
                Assert::contains('\"id\":\"SDCastille\"', $updates[0], 'full deck json stored, quotes escaped');
                Assert::same([['playerId' => 1, 'transition' => 'deckPicked']], $game->gamestate->nonMultiactive, 'released');
                Assert::same([], $game->tournamentStores, 'not a tournament: nothing stored');
            },

            'actPickDeck rejects an unknown starter deck without touching the DB' => function () {
                $game = new FrameworkActionsGame();

                Assert::throws(fn() => $game->actPickDeck('starter', 'SDNope', ''), 'SDNope is not a starter valid deck', 'rejected');

                Assert::same([], $game->dbQueries, 'nothing written');
                Assert::same([], $game->gamestate->nonMultiactive, 'player still has to choose');
            },

            'actPickDeck in a tournament also stores the decoded deck object for later rounds' => function () {
                $game = new FrameworkActionsGame();
                $game->tournament = true;

                $game->actPickDeck('starter', 'SDCastille', '');

                Assert::count(1, $game->tournamentStores, 'stored once');
                $store = $game->tournamentStores[0];
                Assert::same(1, $store['playerId'], 'player');
                Assert::same('deck_source', $store['key'], 'key');
                // WHY: the framework JSON-encodes on store, so it must get the object, not the addslashes'd string.
                Assert::true(is_object($store['value']), 'decoded deck object, not a string');
                Assert::same('SDCastille', $store['value']->id, 'the picked deck');
                $private = array_filter($game->notify->messages, fn($m) => str_contains($m['message'], 'stored for the tournament'));
                Assert::count(1, $private, 'tournament notice sent');
                Assert::same([['playerId' => 1, 'transition' => 'deckPicked']], $game->gamestate->nonMultiactive, 'released');
            },

            'actPickDeck rejects malformed custom deck JSON' => function () {
                $game = new FrameworkActionsGame();
                Assert::throws(fn() => $game->actPickDeck('custom', 'x', 'this is not json'), 'Invalid deck JSON', 'rejected');
                Assert::same([], $game->dbQueries, 'nothing written');
            },

            'actPickDeck reports every validation error privately and leaves the player un-released' => function () {
                $game = new FrameworkActionsGame();

                // An empty deck has no name and no faction - DeckValidator reports both and stops early.
                $game->actPickDeck('custom', 'x', '{}');

                $private = array_values(array_filter($game->notify->messages, fn($m) => $m['channel'] === 'player:1'));
                Assert::count(3, $private, 'header + one line per error');
                Assert::contains('errors were encountered', $private[0]['message'], 'header first');
                Assert::same('The deck has no name.', $private[1]['message'], 'first error');
                Assert::same('The deck has no faction.', $private[2]['message'], 'second error');
                Assert::same([], $game->dbQueries, 'invalid deck is never stored');
                Assert::same([], $game->gamestate->nonMultiactive, 'player must pick again');
            },

            'actPickDeck rejects an unknown deck type' => function () {
                $game = new FrameworkActionsGame();
                Assert::throws(fn() => $game->actPickDeck('mystery', 'x', '{}'), 'mystery is not a valid deck type', 'rejected');
                Assert::same([], $game->gamestate->nonMultiactive, 'not released');
            },

            // ---- actDayPlanned ------------------------------------------------------------------

            'actDayPlanned records the choice, parks both cards and releases the player' => function () {
                $game = new FrameworkActionsGame();
                $game->currentPlayerId = 2;

                $game->actDayPlanned(11, 12);

                $updates = $game->queriesMatching('UPDATE player SET selected_scheme_id');
                Assert::count(1, $updates, 'one update');
                Assert::contains("selected_scheme_id = '11'", $updates[0], 'scheme');
                Assert::contains("selected_character_id = '12'", $updates[0], 'character');
                Assert::contains("player_id='2'", $updates[0], 'player');
                // WHY: parked so an F5 does not show them back in the approach deck while others finish planning.
                Assert::same([11, 12], $game->parkedCardIds, 'parked');
                Assert::same([['playerId' => 2, 'transition' => 'dayPlanned']], $game->gamestate->nonMultiactive, 'released');
            },

            'actDayPlanned only parks the cards that were actually chosen' => function () {
                $game = new FrameworkActionsGame();
                $game->actDayPlanned(0, 12);
                Assert::same([12], $game->parkedCardIds, 'no scheme chosen');

                $game = new FrameworkActionsGame();
                $game->actDayPlanned(11, 0);
                Assert::same([11], $game->parkedCardIds, 'no character chosen');

                $game = new FrameworkActionsGame();
                $game->actDayPlanned(0, 0);
                Assert::same([], $game->parkedCardIds, 'nothing chosen');
                Assert::count(1, $game->gamestate->nonMultiactive, 'still released');
            },

            // ---- actCityLocationsForRenownSelected ----------------------------------------------

            'actCityLocationsForRenownSelected checks then queues one Renown event per location' => function () {
                $game = new FrameworkActionsGame();
                $game->activePlayerId = 2;

                $game->actCityLocationsForRenownSelected(json_encode([Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM]));

                $events = $game->queued(EventRenownAddedToLocation::class);
                Assert::count(2, $events, 'two events');
                Assert::same(Game::LOCATION_CITY_DOCKS, $events[0]->location, 'first location');
                Assert::same(Game::LOCATION_CITY_FORUM, $events[1]->location, 'second location');
                foreach ($events as $event) {
                    Assert::same(2, $event->playerId, 'attributed to the active player');
                    Assert::same(1, $event->amount, 'one Renown each');
                }
                Assert::count(2, $game->theah->checkedEvents, 'each event was offered to the cards first');
                Assert::same([''], $game->gamestate->transitions, 'returns to finish the Scheme events');
            },

            'actCityLocationsForRenownSelected refuses the same location twice' => function () {
                $game = new FrameworkActionsGame();

                Assert::throws(
                    fn() => $game->actCityLocationsForRenownSelected(json_encode([Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_DOCKS])),
                    'different locations',
                    'duplicates refused'
                );

                Assert::count(0, $game->queued(EventRenownAddedToLocation::class), 'nothing queued');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actCityLocationsForRenownSelected allows a single location' => function () {
                $game = new FrameworkActionsGame();
                $game->actCityLocationsForRenownSelected(json_encode([Game::LOCATION_CITY_BAZAAR]));
                Assert::count(1, $game->queued(EventRenownAddedToLocation::class), 'queued');
            },

            'actCityLocationsForRenownSelected rejects a location that is not in the city' => function () {
                $game = new FrameworkActionsGame();

                $error = Assert::throws(
                    fn() => $game->actCityLocationsForRenownSelected(json_encode([Game::LOCATION_CITY_DOCKS, 'Atlantis'])),
                    'Location Atlantis does not exist',
                    'unknown location rejected'
                );
                // WHY: a client-supplied bad location must be a player-facing error, not the bare \Exception getCityLocation() throws.
                Assert::instanceOf(\Bga\GameFramework\UserException::class, $error, 'user-facing exception type');

                Assert::count(0, $game->queued(EventRenownAddedToLocation::class), 'validation precedes queueing: even the valid location is not queued');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actCityLocationsForRenownSelected: a card veto on any location queues nothing at all' => function () {
                $game = new FrameworkActionsGame();
                $game->theah->eventCheckHook = function (Event $event) {
                    if ($event instanceof EventRenownAddedToLocation && $event->location === Game::LOCATION_CITY_FORUM) {
                        throw new \Exception('Forum cannot gain Renown');
                    }
                };

                Assert::throws(
                    fn() => $game->actCityLocationsForRenownSelected(json_encode([Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM])),
                    'Forum cannot gain Renown',
                    'veto propagates'
                );

                // WHY: all events are checked before any is queued, so a late veto cannot leave a half-applied choice.
                Assert::count(0, $game->queued(EventRenownAddedToLocation::class), 'nothing queued');
            },

            // ---- actDuskPhaseCardsDiscarded -----------------------------------------------------

            'actDuskPhaseCardsDiscarded parks exactly the hand excess over Panache and releases the player' => function () {
                $game = new FrameworkActionsGame();
                $game->currentPlayerId = 1;
                $leader = new GenericLeader();
                $leader->Panache = 2;
                $game->place($leader, 'Leader Zone', 1);
                $game->theah->leadersByPlayerId[1] = $leader;
                $hand = [];
                for ($i = 0; $i < 4; $i++) {
                    $hand[] = $game->place(new GenericCharacter("Hand $i"), Game::LOCATION_HAND, 1);
                }

                $game->actDuskPhaseCardsDiscarded(json_encode([$hand[1]->Id, $hand[3]->Id]));

                Assert::same([$hand[1]->Id, $hand[3]->Id], $game->parkedCardIds, 'parked the chosen two');
                Assert::same([['playerId' => 1, 'transition' => 'cardsDiscarded']], $game->gamestate->nonMultiactive, 'released');
            },

            'actDuskPhaseCardsDiscarded refuses discarding too few or too many cards' => function () {
                $game = new FrameworkActionsGame();
                $leader = new GenericLeader();
                $leader->Panache = 2;
                $game->place($leader, 'Leader Zone', 1);
                $game->theah->leadersByPlayerId[1] = $leader;
                $hand = [];
                for ($i = 0; $i < 4; $i++) {
                    $hand[] = $game->place(new GenericCharacter("Hand $i"), Game::LOCATION_HAND, 1);
                }

                Assert::throws(fn() => $game->actDuskPhaseCardsDiscarded(json_encode([$hand[0]->Id])), 'exactly 2 card(s)', 'too few');
                Assert::throws(
                    fn() => $game->actDuskPhaseCardsDiscarded(json_encode([$hand[0]->Id, $hand[1]->Id, $hand[2]->Id])),
                    'exactly 2 card(s)',
                    'too many'
                );

                Assert::same([], $game->parkedCardIds, 'nothing parked on failure');
                Assert::same([], $game->gamestate->nonMultiactive, 'player not released');
            },

            // ---- actChooseWhenRevealedCard ------------------------------------------------------

            'actChooseWhenRevealedCard queues the chosen effect and keeps the others pending' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::WHEN_REVEALED_REMAINING_CARDS, json_encode([
                    ['cardId' => 5, 'playerId' => 1],
                    ['cardId' => 6, 'playerId' => 2],
                    ['cardId' => 7, 'playerId' => 1],
                ]));

                $game->actChooseWhenRevealedCard(6);

                $events = $game->queued(EventCardWhenRevealedEffect::class);
                Assert::count(1, $events, 'one effect');
                Assert::same(2, $events[0]->playerId, 'owner of the chosen card');
                Assert::same(6, $events[0]->cardId, 'chosen card');
                Assert::same(
                    [['cardId' => 5, 'playerId' => 1], ['cardId' => 7, 'playerId' => 1]],
                    json_decode($game->globals->get(Game::WHEN_REVEALED_REMAINING_CARDS), true),
                    'others remain, order preserved'
                );
                Assert::same([''], $game->gamestate->transitions, 'back to the reveal loop');
            },

            'actChooseWhenRevealedCard empties the pending list when the last card is chosen' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::WHEN_REVEALED_REMAINING_CARDS, json_encode([['cardId' => 5, 'playerId' => 1]]));

                $game->actChooseWhenRevealedCard(5);

                Assert::same('[]', $game->globals->get(Game::WHEN_REVEALED_REMAINING_CARDS), 'empty list');
            },

            'actChooseWhenRevealedCard rejects a card that is not pending' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::WHEN_REVEALED_REMAINING_CARDS, json_encode([['cardId' => 5, 'playerId' => 1]]));

                Assert::throws(fn() => $game->actChooseWhenRevealedCard(99), 'Invalid card selection', 'not pending');

                Assert::count(0, $game->queued(EventCardWhenRevealedEffect::class), 'nothing queued');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actChooseWhenRevealedCard rejects any choice when nothing is pending' => function () {
                $game = new FrameworkActionsGame();
                Assert::throws(fn() => $game->actChooseWhenRevealedCard(5), 'Invalid card selection', 'nothing pending');
            },

            // ---- actChooseNextReaction ----------------------------------------------------------

            'actChooseNextReaction promotes the chosen reaction and defers its siblings' => function () {
                $game = new FrameworkActionsGame();
                $a = $this->reactionTransition($game, 1, '01001_1');
                $b = $this->reactionTransition($game, 1, '01002_1');
                $c = $this->reactionTransition($game, 2, '01003_1');

                $game->actChooseNextReaction(json_encode([spl_object_id($b)]));

                Assert::true($b->runImmediately, 'chosen runs first');
                Assert::false($a->runImmediately, 'sibling not promoted');
                Assert::same(Event::REACTION_PRIORITY, $b->priority, 'chosen keeps its priority so pay runs next');
                // WHY: siblings are demoted so the chosen reaction's pay step drains before chooseNext is offered again.
                Assert::same(Event::DEFERRED_REACTION_PRIORITY, $a->priority, 'sibling a deferred');
                Assert::same(Event::DEFERRED_REACTION_PRIORITY, $c->priority, 'sibling c deferred');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'actChooseNextReaction promotes several reactions of one player together (one shared button)' => function () {
                $game = new FrameworkActionsGame();
                $a = $this->reactionTransition($game, 1, '01001_1');
                $b = $this->reactionTransition($game, 1, '01002_1');
                $c = $this->reactionTransition($game, 2, '01003_1');

                $game->actChooseNextReaction(json_encode([spl_object_id($a), spl_object_id($b)]));

                Assert::true($a->runImmediately && $b->runImmediately, 'both promoted');
                Assert::false($c->runImmediately, 'other player not promoted');
                Assert::same(Event::DEFERRED_REACTION_PRIORITY, $c->priority, 'other deferred');
                Assert::same(Event::REACTION_PRIORITY, $a->priority, 'a untouched');
                Assert::same(Event::REACTION_PRIORITY, $b->priority, 'b untouched');
            },

            'opponent in-hand reactions share one button' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::FIRST_PLAYER, 1);
                $fp = $this->queuedHandRisk($game, 1, 'FP Risk');
                $a = $this->queuedHandRisk($game, 2, 'Hidden A');
                $b = $this->queuedHandRisk($game, 2, 'Hidden B');

                $choices = $game->buildChooseNextReactionChoices();
                Assert::count(2, $choices, 'opponent risks collapse to one button');
                Assert::same(
                    'Player One - FP Risk - In-Hand - Test Risk Reaction',
                    $choices[0]['label'],
                    'First Player still sees their own card'
                );
                Assert::same([spl_object_id($fp)], $choices[0]['eventIds'], 'FP button is one reaction');
                Assert::same('Player Two - Risk Reaction', $choices[1]['label'], 'opponent label stays fogged');
                Assert::same(
                    [spl_object_id($a), spl_object_id($b)],
                    $choices[1]['eventIds'],
                    'one click releases both opponent reactions'
                );

                $fogLabels = array_column($game->buildChooseNextReactionChoices(true), 'label');
                Assert::same([
                    'Player One - Risk Reaction',
                    'Player Two - Risk Reaction',
                ], $fogLabels, 'public log hides every risk name');
            },

            'a lone opponent in-hand reaction keeps the fogged label' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::FIRST_PLAYER, 1);
                $only = $this->queuedHandRisk($game, 2, 'Only One');

                $choices = $game->buildChooseNextReactionChoices();
                Assert::count(1, $choices, 'one button');
                Assert::same('Player Two - Risk Reaction', $choices[0]['label'], 'fogged');
                Assert::same([spl_object_id($only)], $choices[0]['eventIds'], 'one event');
            },

            'hand reactions from one player are released without a First Player choice' => function () {
                $game = new FrameworkActionsGame();
                $a = $this->queuedHandRisk($game, 2, 'Hidden A');
                $b = $this->queuedHandRisk($game, 2, 'Hidden B');
                $queued = $game->theah->getQueuedReactionTransitionEvents();

                Assert::true($game->theah->releaseHandReactionsFromOnePlayer($queued), 'released');
                Assert::true($a->runImmediately && $b->runImmediately, 'both run in queue order');
                Assert::same(Event::REACTION_PRIORITY, $a->priority, 'a not deferred');
                Assert::same(Event::REACTION_PRIORITY, $b->priority, 'b not deferred');
            },

            'hand reactions from different players still need a First Player choice' => function () {
                $game = new FrameworkActionsGame();
                $a = $this->queuedHandRisk($game, 1, 'Mine');
                $b = $this->queuedHandRisk($game, 2, 'Theirs');

                Assert::false(
                    $game->theah->releaseHandReactionsFromOnePlayer($game->theah->getQueuedReactionTransitionEvents()),
                    'two players'
                );
                Assert::false($a->runImmediately || $b->runImmediately, 'nothing released');
            },

            'a hand reaction mixed with an in-play reaction still needs a First Player choice' => function () {
                $game = new FrameworkActionsGame();
                $hand = $this->queuedHandRisk($game, 2, 'From Hand');
                $board = new GenericRisk('On the table');
                $board->addReaction(new GenericRiskReaction(), $game, false);
                $game->place($board, Game::LOCATION_CITY_DOCKS, 2);
                $reaction = $board->getReactions()[0];
                $played = EventFactory::createTransitionEvent(2, $board->Id, 'reaction', $reaction->Id);
                $played->priority = Event::REACTION_PRIORITY;
                $game->theah->queueEvent($played);

                Assert::false(
                    $game->theah->releaseHandReactionsFromOnePlayer($game->theah->getQueuedReactionTransitionEvents()),
                    'not all in hand'
                );
                Assert::false($hand->runImmediately || $played->runImmediately, 'nothing released');
            },

            'actChooseNextReaction coerces string ids from the client' => function () {
                $game = new FrameworkActionsGame();
                $a = $this->reactionTransition($game, 1, '01001_1');

                $game->actChooseNextReaction(json_encode([(string) spl_object_id($a)]));

                Assert::true($a->runImmediately, 'string id accepted');
            },

            'actChooseNextReaction rejects an empty or non-list selection' => function () {
                $game = new FrameworkActionsGame();
                $this->reactionTransition($game, 1, '01001_1');

                Assert::throws(fn() => $game->actChooseNextReaction('[]'), 'Invalid reaction selection', 'empty list');
                Assert::throws(fn() => $game->actChooseNextReaction('"abc"'), 'Invalid reaction selection', 'not an array');
                Assert::throws(fn() => $game->actChooseNextReaction('not json'), 'Invalid reaction selection', 'not json');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actChooseNextReaction rejects an event id that is not a queued reaction' => function () {
                $game = new FrameworkActionsGame();
                $a = $this->reactionTransition($game, 1, '01001_1');

                Assert::throws(fn() => $game->actChooseNextReaction(json_encode([123456789])), 'Invalid reaction selection', 'unknown id');
                Assert::throws(
                    fn() => $game->actChooseNextReaction(json_encode([spl_object_id($a), 123456789])),
                    'Invalid reaction selection',
                    'one bad id spoils the selection'
                );

                Assert::false($a->runImmediately, 'nothing promoted by a rejected selection');
                Assert::same(Event::REACTION_PRIORITY, $a->priority, 'nothing deferred either');
            },

            'actChooseNextReaction only offers the highest-priority tier' => function () {
                $game = new FrameworkActionsGame();
                $now = $this->reactionTransition($game, 1, '01001_1');
                $later = $this->reactionTransition($game, 1, '01002_1', Event::DEFERRED_REACTION_PRIORITY);

                Assert::throws(
                    fn() => $game->actChooseNextReaction(json_encode([spl_object_id($later)])),
                    'Invalid reaction selection',
                    'deferred reaction is not choosable yet'
                );
                Assert::false($later->runImmediately, 'not promoted');

                $game->actChooseNextReaction(json_encode([spl_object_id($now)]));
                Assert::true($now->runImmediately, 'current tier is choosable');
            },

            'actChooseNextReaction is refused while a more urgent event is still queued' => function () {
                $game = new FrameworkActionsGame();
                $a = $this->reactionTransition($game, 1, '01001_1');
                // A default-priority (MEDIUM, 3) event drains before REACTION_PRIORITY (6), so the
                // reaction tier is not the "current" tier yet and nothing in it may be chosen.
                $urgent = EventFactory::createRiskPlayedEvent(1, 5);
                Assert::true($urgent->priority < Event::REACTION_PRIORITY, 'fixture: urgent event outranks reactions');
                $game->theah->queueEvent($urgent);

                Assert::throws(fn() => $game->actChooseNextReaction(json_encode([spl_object_id($a)])), 'Invalid reaction selection', 'tier not reached');
                Assert::false($a->runImmediately, 'not promoted');
            },

            'actChooseNextReaction with nothing queued is rejected' => function () {
                $game = new FrameworkActionsGame();
                Assert::throws(fn() => $game->actChooseNextReaction('[1]'), 'Invalid reaction selection', 'empty queue');
            },
        ];
    }
}
