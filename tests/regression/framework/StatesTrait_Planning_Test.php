<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\StatesTraitGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardWhenRevealedEffect;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventFirstPlayerDetermined;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseMuster;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningBeginning;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;

/** StatesTrait: Planning phase states. */
class StatesTrait_Planning_Test extends TestCase
{
    // WHY: Leaders are Characters. Parked off-board so they do not count against Crew Cap in the tests.
    private const OFF_BOARD = 'Leader Zone';

    public function name(): string
    {
        return 'StatesTrait: planning phase';
    }

    /** Character whose card text has a When Revealed effect. */
    private function revealCharacter(string $name = 'Reveal Char'): GenericCharacter
    {
        return new class($name) extends GenericCharacter {
            public function hasWhenRevealedEffect(): bool
            {
                return true;
            }
        };
    }

    /**
     * Seed two players, each with a scheme (initiatives given) and a character.
     *
     * @return array{0:GenericScheme,1:GenericScheme}
     */
    private function seedSchemes(StatesTraitGame $game, int $initiative1, int $initiative2): array
    {
        $s1 = $game->place(new GenericScheme('Scheme One', $initiative1), Game::LOCATION_HAND, 1, 2001);
        $s2 = $game->place(new GenericScheme('Scheme Two', $initiative2), Game::LOCATION_HAND, 2, 2002);
        $game->playerRows = [
            1 => ['player_id' => 1, 'player_name' => 'Player One', 'schemeId' => 2001, 'characterId' => 0, 'leaderId' => 0],
            2 => ['player_id' => 2, 'player_name' => 'Player Two', 'schemeId' => 2002, 'characterId' => 0, 'leaderId' => 0],
        ];
        return [$s1, $s2];
    }

    public function tests(): array
    {
        return [
            'stPlanningPhaseBeginning sets the Planning phase and queues PhasePlanningBeginning' => function () {
                $game = new StatesTraitGame();
                $game->stPlanningPhaseBeginning();
                Assert::same(Game::PLANNING, $game->getGameStateValue(Game::TURN_PHASE), 'turn phase');
                Assert::count(1, $game->queued(EventPhasePlanningBeginning::class), 'event');
                Assert::same('planningPhase', $game->notify->messages[0]['type'], 'notification');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stPlanningPhase makes all players active' => function () {
                $game = new StatesTraitGame();
                $game->stPlanningPhase();
                Assert::same(1, $game->gamestate->allMultiactiveCalls, 'multiactive');
            },

            // ---- approach cards -------------------------------------------------------

            'stPlanningPhaseApproachCardsPlayed reveals schemes at Home and announces approach characters' => function () {
                $game = new StatesTraitGame();
                $game->place(new GenericScheme('Scheme One'), Game::LOCATION_HAND, 1, 2001);
                $game->place(new GenericCharacter('Char One'), Game::LOCATION_HAND, 1, 3001);
                $game->place(new GenericCharacter('Char Two'), Game::LOCATION_HAND, 2, 3002);
                $game->playerRows = [
                    1 => ['player_id' => 1, 'player_name' => 'Player One', 'schemeId' => 2001, 'characterId' => 3001],
                    2 => ['player_id' => 2, 'player_name' => 'Player Two', 'schemeId' => 0, 'characterId' => 3002],
                ];

                $game->stPlanningPhaseApproachCardsPlayed();

                Assert::same(Game::LOCATION_PLAYER_HOME, $game->getCardObjectFromDb(2001)->Location, 'scheme moved Home');
                $revealed = $game->queued(EventSchemeCardRevealed::class);
                Assert::count(1, $revealed, 'only the player with a scheme reveals one');
                // EventSchemeCardRevealed declares playerId as string; PHP coerces the int key.
                Assert::same('1', $revealed[0]->playerId, 'scheme reveal player');
                Assert::same(2001, $revealed[0]->schemeId, 'scheme id');
                Assert::same(Game::LOCATION_PLAYER_HOME, $revealed[0]->location, 'scheme location');
                Assert::same('Player One', $revealed[0]->playerName, 'player name');

                $approach = $game->queued(EventApproachCharacterPlayed::class);
                Assert::count(2, $approach, 'both characters announced');
                Assert::same([1, 3001], [$approach[0]->playerId, $approach[0]->characterId], 'first approach');
                Assert::same([2, 3002], [$approach[1]->playerId, $approach[1]->characterId], 'second approach');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stPlanningPhaseApproachCardsPlayed queues every scheme reveal before any character' => function () {
                $game = new StatesTraitGame();
                $game->place(new GenericScheme('Scheme One'), Game::LOCATION_HAND, 1, 2001);
                $game->place(new GenericScheme('Scheme Two'), Game::LOCATION_HAND, 2, 2002);
                $game->place(new GenericCharacter('Char One'), Game::LOCATION_HAND, 1, 3001);
                $game->playerRows = [
                    1 => ['player_id' => 1, 'player_name' => 'Player One', 'schemeId' => 2001, 'characterId' => 3001],
                    2 => ['player_id' => 2, 'player_name' => 'Player Two', 'schemeId' => 2002, 'characterId' => 0],
                ];

                $game->stPlanningPhaseApproachCardsPlayed();

                $order = array_map(fn($e) => $e::class, $game->theah->queuedEvents);
                Assert::same(
                    [EventSchemeCardRevealed::class, EventSchemeCardRevealed::class, EventApproachCharacterPlayed::class],
                    $order,
                    'schemes first, then characters'
                );
            },

            // ---- first player --------------------------------------------------------

            'stPlanningPhaseDetermineFirstPlayer: highest initiative wins outright' => function () {
                $game = new StatesTraitGame();
                $this->seedSchemes($game, 2, 5);

                $game->stPlanningPhaseDetermineFirstPlayer();

                Assert::same(2, $game->globals->get(Game::FIRST_PLAYER), 'first player');
                Assert::same(2, $game->globals->get(Game::CURRENT_PLAYER), 'current player');
                Assert::same([2], $game->newPlayerOrderCalls, 'turn order rebuilt from winner');
                Assert::count(1, $game->queued(EventFirstPlayerDetermined::class), 'event');
                $note = $game->notify->messages[0];
                Assert::same('firstPlayer', $note['type'], 'notification type');
                Assert::same(5, $note['args']['initiative'], 'initiative announced');
                Assert::same('Player Two', $note['args']['player_name'], 'winner announced');
                Assert::contains('highest initiative', $note['message'], 'clear-winner message');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stPlanningPhaseDetermineFirstPlayer: a tie rotates to the player after the previous first player' => function () {
                $game = new StatesTraitGame();
                $this->seedSchemes($game, 4, 4);
                $game->globals->set(Game::FIRST_PLAYER, 1);

                $game->stPlanningPhaseDetermineFirstPlayer();

                Assert::same(2, $game->globals->get(Game::FIRST_PLAYER), 'next player in order');
                Assert::contains('next player in order', $game->notify->messages[0]['message'], 'rotation message');
            },

            'stPlanningPhaseDetermineFirstPlayer: a tie rotation wraps past the last player' => function () {
                $game = new StatesTraitGame();
                $this->seedSchemes($game, 4, 4);
                $game->globals->set(Game::FIRST_PLAYER, 2);

                $game->stPlanningPhaseDetermineFirstPlayer();

                Assert::same(1, $game->globals->get(Game::FIRST_PLAYER), 'wraps to player one');
            },

            'stPlanningPhaseDetermineFirstPlayer: a tie with no previous first player picks a tied player at random' => function () {
                $game = new StatesTraitGame();
                $this->seedSchemes($game, 3, 3);

                $game->stPlanningPhaseDetermineFirstPlayer();

                $first = $game->globals->get(Game::FIRST_PLAYER);
                Assert::true(in_array($first, [1, 2], true), 'one of the tied players');
                Assert::contains('chosen randomly', $game->notify->messages[0]['message'], 'random message');
            },

            'stPlanningPhaseDetermineFirstPlayer: the tied set only contains the highest initiative' => function () {
                $game = new StatesTraitGame();
                $game->playerNames[3] = 'Player Three';
                $game->place(new GenericScheme('A', 4), Game::LOCATION_HAND, 1, 2001);
                $game->place(new GenericScheme('B', 1), Game::LOCATION_HAND, 2, 2002);
                $game->place(new GenericScheme('C', 4), Game::LOCATION_HAND, 3, 2003);
                $game->playerRows = [
                    1 => ['player_id' => 1, 'player_name' => 'Player One', 'schemeId' => 2001],
                    2 => ['player_id' => 2, 'player_name' => 'Player Two', 'schemeId' => 2002],
                    3 => ['player_id' => 3, 'player_name' => 'Player Three', 'schemeId' => 2003],
                ];

                // Random tiebreak: repeat so a leak of the low-initiative player would show.
                for ($i = 0; $i < 20; $i++) {
                    $game->globals->delete(Game::FIRST_PLAYER);
                    $game->stPlanningPhaseDetermineFirstPlayer();
                    Assert::true(in_array($game->globals->get(Game::FIRST_PLAYER), [1, 3], true), 'never the low-initiative player');
                }
            },

            'stPlanningPhaseDetermineFirstPlayer throws when no player has a scheme' => function () {
                $game = new StatesTraitGame();
                $game->playerRows = [
                    1 => ['player_id' => 1, 'player_name' => 'Player One', 'schemeId' => 0],
                    2 => ['player_id' => 2, 'player_name' => 'Player Two', 'schemeId' => 0],
                ];
                $threw = false;
                try {
                    $game->stPlanningPhaseDetermineFirstPlayer();
                } catch (\Exception $e) {
                    $threw = true;
                    Assert::contains('no player has a valid scheme', $e->getMessage(), 'message');
                }
                Assert::true($threw, 'exception thrown');
                Assert::same([], $game->gamestate->transitions, 'did not continue');
            },

            // ---- When Revealed -------------------------------------------------------

            'stPlanningPhaseResolveWhenRevealedCards with none goes straight to resolve' => function () {
                $game = new StatesTraitGame();
                $this->seedSchemes($game, 1, 2);
                $game->place(new GenericCharacter('Plain'), Game::LOCATION_HAND, 1, 3001);
                $game->playerRows[1]['characterId'] = 3001;

                $game->stPlanningPhaseResolveWhenRevealedCards();

                Assert::count(0, $game->queued(EventCardWhenRevealedEffect::class), 'nothing queued');
                Assert::same(['resolve'], $game->gamestate->transitions, 'resolve');
                Assert::same('message', $game->notify->messages[0]['type'], 'status message');
            },

            'stPlanningPhaseResolveWhenRevealedCards with one queues it and clears the remaining list' => function () {
                $game = new StatesTraitGame();
                $this->seedSchemes($game, 1, 2);
                $game->place($this->revealCharacter(), Game::LOCATION_HAND, 1, 3001);
                $game->playerRows[1]['characterId'] = 3001;

                $game->stPlanningPhaseResolveWhenRevealedCards();

                $queued = $game->queued(EventCardWhenRevealedEffect::class);
                Assert::count(1, $queued, 'effect queued');
                Assert::same([1, 3001], [$queued[0]->playerId, $queued[0]->cardId], 'player/card');
                Assert::same('[]', $game->globals->get(Game::WHEN_REVEALED_REMAINING_CARDS), 'remaining list emptied');
                Assert::same(['resolve'], $game->gamestate->transitions, 'resolve');
            },

            'stPlanningPhaseResolveWhenRevealedCards with several defers to the first player to choose order' => function () {
                $game = new StatesTraitGame();
                $this->seedSchemes($game, 1, 2);
                $game->place($this->revealCharacter('R1'), Game::LOCATION_HAND, 1, 3001);
                $game->place($this->revealCharacter('R2'), Game::LOCATION_HAND, 2, 3002);
                $game->playerRows[1]['characterId'] = 3001;
                $game->playerRows[2]['characterId'] = 3002;
                $game->globals->set(Game::FIRST_PLAYER, 2);

                $game->stPlanningPhaseResolveWhenRevealedCards();

                Assert::count(0, $game->queued(EventCardWhenRevealedEffect::class), 'nothing queued yet');
                $remaining = json_decode($game->globals->get(Game::WHEN_REVEALED_REMAINING_CARDS), true);
                Assert::same(
                    [['playerId' => 1, 'cardId' => 3001], ['playerId' => 2, 'cardId' => 3002]],
                    $remaining,
                    'both stored in player order'
                );
                Assert::same([2], $game->gamestate->activePlayerChanges, 'first player chooses');
                Assert::same(['chooseOrder'], $game->gamestate->transitions, 'chooseOrder');
            },

            'stPlanningPhaseResolveWhenRevealedCards counts scheme When Revealed effects too' => function () {
                $game = new StatesTraitGame();
                $game->place(new GenericScheme('Reveals', 3, true), Game::LOCATION_HAND, 1, 2001);
                $game->place(new GenericScheme('Quiet', 1, false), Game::LOCATION_HAND, 2, 2002);
                $game->playerRows = [
                    1 => ['player_id' => 1, 'player_name' => 'Player One', 'schemeId' => 2001, 'characterId' => 0],
                    2 => ['player_id' => 2, 'player_name' => 'Player Two', 'schemeId' => 2002, 'characterId' => 0],
                ];

                $game->stPlanningPhaseResolveWhenRevealedCards();

                $queued = $game->queued(EventCardWhenRevealedEffect::class);
                Assert::count(1, $queued, 'only the revealing scheme');
                Assert::same(2001, $queued[0]->cardId, 'scheme id');
            },

            'stPlanningPhaseResolveWhenRevealedCardsCheckRemaining with none left is done' => function () {
                $game = new StatesTraitGame();
                $game->stPlanningPhaseResolveWhenRevealedCardsCheckRemaining();
                Assert::same(['done'], $game->gamestate->transitions, 'done when global missing');

                $game = new StatesTraitGame();
                $game->globals->set(Game::WHEN_REVEALED_REMAINING_CARDS, json_encode([]));
                $game->stPlanningPhaseResolveWhenRevealedCardsCheckRemaining();
                Assert::same(['done'], $game->gamestate->transitions, 'done when list empty');
            },

            'stPlanningPhaseResolveWhenRevealedCardsCheckRemaining with one left queues it and resolves' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::WHEN_REVEALED_REMAINING_CARDS, json_encode([['playerId' => 2, 'cardId' => 3002]]));

                $game->stPlanningPhaseResolveWhenRevealedCardsCheckRemaining();

                $queued = $game->queued(EventCardWhenRevealedEffect::class);
                Assert::count(1, $queued, 'queued');
                Assert::same([2, 3002], [$queued[0]->playerId, $queued[0]->cardId], 'player/card');
                Assert::same('[]', $game->globals->get(Game::WHEN_REVEALED_REMAINING_CARDS), 'list cleared');
                Assert::same(['resolve'], $game->gamestate->transitions, 'resolve');
            },

            'stPlanningPhaseResolveWhenRevealedCardsCheckRemaining with several left asks the first player again' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::FIRST_PLAYER, 1);
                $game->globals->set(Game::WHEN_REVEALED_REMAINING_CARDS, json_encode([
                    ['playerId' => 1, 'cardId' => 3001],
                    ['playerId' => 2, 'cardId' => 3002],
                ]));

                $game->stPlanningPhaseResolveWhenRevealedCardsCheckRemaining();

                Assert::count(0, $game->queued(EventCardWhenRevealedEffect::class), 'nothing queued');
                Assert::same([1], $game->gamestate->activePlayerChanges, 'first player chooses');
                Assert::same(['chooseOrder'], $game->gamestate->transitions, 'chooseOrder');
            },

            // ---- muster / resolve / draw / end ---------------------------------------

            'stPlanningPhaseMuster reports crew cap status and queues PhaseMuster' => function () {
                $game = new StatesTraitGame();
                $leader1 = $game->place(new GenericLeader('Leader One'), self::OFF_BOARD, 1, 1);
                $leader2 = $game->place(new GenericLeader('Leader Two'), self::OFF_BOARD, 2, 2);
                $leader1->CrewCap = 1;
                $leader2->CrewCap = 5;
                $game->theah->leadersByPlayerId = [1 => $leader1, 2 => $leader2];
                $game->playerRows = [
                    1 => ['player_id' => 1, 'player_name' => 'Player One', 'leaderId' => 1],
                    2 => ['player_id' => 2, 'player_name' => 'Player Two', 'leaderId' => 2],
                ];
                // Player 1 has two characters at Home (over a cap of 1); player 2 has none.
                $game->place(new GenericCharacter('A'), Game::LOCATION_PLAYER_HOME, 1);
                $game->place(new GenericCharacter('B'), Game::LOCATION_PLAYER_HOME, 1);

                $game->stPlanningPhaseMuster();

                $messages = $game->notify->messages;
                Assert::contains('MUSTER', $messages[0]['message'], 'intro');
                Assert::contains('over their Crew Cap', $messages[1]['message'], 'player 1 over cap');
                Assert::same(1, $messages[1]['args']['crewcap'], 'cap shown');
                Assert::same('Player One', $messages[1]['args']['player_name'], 'player 1 named');
                Assert::contains('under their Crew Cap', $messages[2]['message'], 'player 2 under cap');
                Assert::count(1, $game->queued(EventPhaseMuster::class), 'muster event');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stPlanningPhaseMuster treats a crew exactly at the cap as under it' => function () {
                $game = new StatesTraitGame();
                $leader = $game->place(new GenericLeader('Leader One'), self::OFF_BOARD, 1, 1);
                $leader->CrewCap = 2;
                $game->theah->leadersByPlayerId = [1 => $leader];
                $game->playerRows = [1 => ['player_id' => 1, 'player_name' => 'Player One', 'leaderId' => 1]];
                $game->place(new GenericCharacter('A'), Game::LOCATION_PLAYER_HOME, 1);
                $game->place(new GenericCharacter('B'), Game::LOCATION_PLAYER_HOME, 1);

                $game->stPlanningPhaseMuster();

                Assert::contains('under their Crew Cap', $game->notify->messages[1]['message'], 'at cap is not over');
            },

            // Regression: $crewCap was never reset, so a player without a Leader reused the previous
            // player's cap (or hit an undefined variable when they were first in turn order).
            'stPlanningPhaseMuster skips the crew cap message for a player with no Leader instead of reusing another cap' => function () {
                $game = new StatesTraitGame();
                $leader2 = $game->place(new GenericLeader('Leader Two'), self::OFF_BOARD, 2, 2);
                $leader2->CrewCap = 1;
                $game->theah->leadersByPlayerId = [2 => $leader2];
                // Player 1 (first in order) and player 3 (after player 2) have no Leader row.
                $game->playerRows = [
                    1 => ['player_id' => 1, 'player_name' => 'Player One', 'leaderId' => 0],
                    2 => ['player_id' => 2, 'player_name' => 'Player Two', 'leaderId' => 2],
                    3 => ['player_id' => 3, 'player_name' => 'Player Three', 'leaderId' => 0],
                ];
                foreach ([1, 2, 3] as $p) {
                    $game->place(new GenericCharacter("A{$p}"), Game::LOCATION_PLAYER_HOME, $p);
                    $game->place(new GenericCharacter("B{$p}"), Game::LOCATION_PLAYER_HOME, $p);
                }

                $game->stPlanningPhaseMuster();

                $messages = $game->notify->messages;
                Assert::count(2, $messages, 'intro plus only the player with a Leader');
                Assert::same('Player Two', $messages[1]['args']['player_name'], 'only player 2 reported');
                Assert::same(1, $messages[1]['args']['crewcap'], 'their own cap');
                Assert::contains('over their Crew Cap', $messages[1]['message'], 'two characters vs cap 1');
                Assert::count(1, $game->queued(EventPhaseMuster::class), 'muster still proceeds');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            // Regression: the under-cap template used to start with a dangling '<span style=' fragment,
            // which rendered as broken markup in the game log.
            'stPlanningPhaseMuster crew cap messages are plain, well-formed templates' => function () {
                $game = new StatesTraitGame();
                $leader1 = $game->place(new GenericLeader('Leader One'), self::OFF_BOARD, 1, 1);
                $leader2 = $game->place(new GenericLeader('Leader Two'), self::OFF_BOARD, 2, 2);
                $leader1->CrewCap = 0;
                $leader2->CrewCap = 5;
                $game->theah->leadersByPlayerId = [1 => $leader1, 2 => $leader2];
                $game->playerRows = [
                    1 => ['player_id' => 1, 'player_name' => 'Player One', 'leaderId' => 1],
                    2 => ['player_id' => 2, 'player_name' => 'Player Two', 'leaderId' => 2],
                ];
                $game->place(new GenericCharacter('A'), Game::LOCATION_PLAYER_HOME, 1);

                $game->stPlanningPhaseMuster();

                Assert::same('${player_name} is over their Crew Cap limit of ${crewcap}', $game->notify->messages[1]['message'], 'over');
                Assert::same('${player_name} is under their Crew Cap limit of ${crewcap}', $game->notify->messages[2]['message'], 'under');
                foreach ($game->notify->messages as $i => $note) {
                    Assert::false(str_contains($note['message'], '<span style=$'), "message {$i} has no dangling span");
                    preg_match_all('/\$\{(\w+)\}/', $note['message'], $m);
                    foreach ($m[1] as $placeholder) {
                        Assert::true(array_key_exists($placeholder, $note['args']), "message {$i}: \${{$placeholder}} has an argument");
                    }
                }
            },

            'stPlanningPhaseResolveSchemes queues ResolveScheme per player with a scheme, in turn order' => function () {
                $game = new StatesTraitGame();
                $s1 = $game->place(new GenericScheme('One'), Game::LOCATION_PLAYER_HOME, 1, 2001);
                $s2 = $game->place(new GenericScheme('Two'), Game::LOCATION_PLAYER_HOME, 2, 2002);
                $game->playerRows = [
                    2 => ['player_id' => 2, 'schemeId' => 2002],
                    1 => ['player_id' => 1, 'schemeId' => 0],
                ];
                $game->playerRows[3] = ['player_id' => 3, 'schemeId' => 2001];

                $game->stPlanningPhaseResolveSchemes();

                $events = $game->queued(EventResolveScheme::class);
                Assert::count(2, $events, 'players without a scheme are skipped');
                Assert::same([2, 'Player Two', $s2], [$events[0]->playerId, $events[0]->playerName, $events[0]->scheme], 'first');
                Assert::same(3, $events[1]->playerId, 'second follows row order');
                Assert::same($s1, $events[1]->scheme, 'second scheme');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stPlanningPhaseDraw draws Panache cards for each player and notifies privately and publicly' => function () {
                $game = new StatesTraitGame();
                $leader1 = $game->place(new GenericLeader('Leader One'), self::OFF_BOARD, 1, 1);
                $leader2 = $game->place(new GenericLeader('Leader Two'), self::OFF_BOARD, 2, 2);
                $leader1->ModifiedPanache = 3;
                $leader2->ModifiedPanache = 1;
                $game->theah->leadersByPlayerId = [1 => $leader1, 2 => $leader2];

                $game->stPlanningPhaseDraw();

                Assert::same([1, 1, 1, 2], $game->drawnFor, 'draws per player');
                Assert::same(2, $game->factionBatchBegins, 'one batch per player');
                Assert::same(2, $game->factionBatchEnds, 'batches closed');

                $private = array_values(array_filter($game->notify->messages, fn($m) => $m['type'] === 'factionResolveCardDraw'));
                Assert::count(2, $private, 'private notifications');
                Assert::same('player:1', $private[0]['channel'], 'sent to player 1');
                Assert::same(3, $private[0]['args']['panache'], 'panache');
                Assert::count(3, $private[0]['args']['cards'], 'cards listed');

                $public = array_values(array_filter($game->notify->messages, fn($m) => $m['type'] === 'factionResolveCardDrawPublic'));
                Assert::count(2, $public, 'public notifications');
                Assert::same(3, $public[0]['args']['count'], 'public count player 1');
                Assert::same(1, $public[1]['args']['count'], 'public count player 2');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stPlanningPhaseDraw draws nothing for a player with zero Panache' => function () {
                $game = new StatesTraitGame();
                $leader1 = $game->place(new GenericLeader('Leader One'), self::OFF_BOARD, 1, 1);
                $leader2 = $game->place(new GenericLeader('Leader Two'), self::OFF_BOARD, 2, 2);
                $leader1->ModifiedPanache = 0;
                $leader2->ModifiedPanache = 2;
                $game->theah->leadersByPlayerId = [1 => $leader1, 2 => $leader2];

                $game->stPlanningPhaseDraw();

                Assert::same([2, 2], $game->drawnFor, 'only player 2 draws');
            },

            'stPlanningPhaseEnd queues PhasePlanningEnd' => function () {
                $game = new StatesTraitGame();
                $game->stPlanningPhaseEnd();
                Assert::count(1, $game->queued(EventPhasePlanningEnd::class), 'event');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },
        ];
    }
}
