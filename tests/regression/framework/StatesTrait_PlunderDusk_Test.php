<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\StatesTraitGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTakeReknownForControlledLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlunderPhaseAdditionalReknownEvent;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlunderPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlunderPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;

/** StatesTrait: Plunder (victory checks, renown payout) and Dusk (cleanup, discard, end of day). */
class StatesTrait_PlunderDusk_Test extends TestCase
{
    // WHY: Leaders are Characters. Parked off-board so they are not "in play" for influence / crew counts.
    private const OFF_BOARD = 'Leader Zone';

    public function name(): string
    {
        return 'StatesTrait: plunder and dusk phases';
    }

    /** @return array<int, GenericLeader> */
    private function leaders(StatesTraitGame $game, array $wounds = [1 => 0, 2 => 0]): array
    {
        $leaders = [];
        foreach ($wounds as $playerId => $w) {
            $leader = $game->place(new GenericLeader("Leader {$playerId}"), self::OFF_BOARD, $playerId, 100 + $playerId);
            $leader->Wounds = $w;
            $leaders[$playerId] = $leader;
        }
        $game->theah->leadersByPlayerId = $leaders;
        return $leaders;
    }

    /** Both players level on `reknown`, on day 5, ready for the fifth-day tiebreak chain. */
    private function fifthDayTie(StatesTraitGame $game, int $reknown = 3): void
    {
        $game->setGameStateValue(Game::DAY, 5);
        $game->setPlayerReknown(1, $reknown);
        $game->setPlayerReknown(2, $reknown);
    }

    private function messages(StatesTraitGame $game): string
    {
        return implode("\n", array_map(fn($m) => $m['message'], $game->notify->messages));
    }

    public function tests(): array
    {
        return [
            // ---- plunder: begin / end ------------------------------------------------

            'stPlunderPhaseBegin sets the Plunder phase and queues PlunderPhaseBegin' => function () {
                $game = new StatesTraitGame();
                $game->stPlunderPhaseBegin();
                Assert::same(Game::PLUNDER, $game->getGameStateValue(Game::TURN_PHASE), 'phase');
                Assert::count(1, $game->queued(EventPlunderPhaseBegin::class), 'event');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stPlunderPhaseEnd queues PlunderPhaseEnd' => function () {
                $game = new StatesTraitGame();
                $game->stPlunderPhaseEnd();
                Assert::count(1, $game->queued(EventPlunderPhaseEnd::class), 'event');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            // ---- dominance -----------------------------------------------------------

            'stPlunderCheckDominanceVictory: controlling all three core locations wins the game' => function () {
                $game = new StatesTraitGame();
                foreach ([Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_BAZAAR] as $site) {
                    $game->setControllerForLocation($site, 2);
                }

                $game->stPlunderCheckDominanceVictory();

                Assert::same([Game::VICTORY_DOMINANCE], $game->endOfGameCalls, 'dominance victory');
                Assert::same([], $game->gamestate->transitions, 'the game ends instead of continuing');
                Assert::same(-1, $game->getPlayerReknown(1), 'losers are pushed below zero');
                Assert::same(0, $game->getPlayerReknown(2), 'winner untouched');
                Assert::contains('DOMINANCE VICTORY', $this->messages($game), 'announced');
            },

            'stPlunderCheckDominanceVictory: split control continues the game' => function () {
                $game = new StatesTraitGame();
                $game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 1);
                $game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 1);
                $game->setControllerForLocation(Game::LOCATION_CITY_BAZAAR, 2);

                $game->stPlunderCheckDominanceVictory();

                Assert::same([], $game->endOfGameCalls, 'no victory');
                Assert::same(['next'], $game->gamestate->transitions, 'next');
            },

            'stPlunderCheckDominanceVictory: three uncontrolled locations are not a victory for "player 0"' => function () {
                $game = new StatesTraitGame();

                $game->stPlunderCheckDominanceVictory();

                Assert::same([], $game->endOfGameCalls, 'no victory');
                Assert::same(['next'], $game->gamestate->transitions, 'next');
            },

            // ---- renown payout -------------------------------------------------------

            'stPlunderGainRenown pays each controlled location to its controller and removes the renown' => function () {
                $game = new StatesTraitGame();
                $game->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $game->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 3);
                $game->theah->setLocationController(Game::LOCATION_CITY_BAZAAR, 2);
                $game->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 1);
                $game->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 5); // uncontrolled: stays put

                $game->stPlunderGainRenown();

                $take = $game->queued(EventPlayerTakeReknownForControlledLocation::class);
                Assert::count(2, $take, 'one per controlled location');
                Assert::same([1, Game::LOCATION_CITY_DOCKS, 3], [$take[0]->playerId, $take[0]->location, $take[0]->reknown], 'docks');
                Assert::same([2, Game::LOCATION_CITY_BAZAAR, 1], [$take[1]->playerId, $take[1]->location, $take[1]->reknown], 'bazaar');

                $gains = $game->queued(EventPlayerGainsReknown::class);
                Assert::same([[1, 3], [2, 1]], array_map(fn($e) => [$e->playerId, $e->amount], $gains), 'gains');

                $removed = $game->queued(EventRenownRemovedFromLocation::class);
                Assert::same(
                    [[Game::LOCATION_CITY_DOCKS, 3], [Game::LOCATION_CITY_BAZAAR, 1]],
                    array_map(fn($e) => [$e->location, $e->amount], $removed),
                    'renown removed only from controlled locations'
                );
                Assert::same('Location Claimed', $removed[0]->source, 'source label');

                Assert::count(1, $game->queued(EventPlunderPhaseAdditionalReknownEvent::class), 'additional-renown hook');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stPlunderGainRenown queues the additional-renown hook even when nothing is controlled' => function () {
                $game = new StatesTraitGame();
                $game->stPlunderGainRenown();
                Assert::count(0, $game->queued(EventPlayerGainsReknown::class), 'no gains');
                Assert::count(1, $game->queued(EventPlunderPhaseAdditionalReknownEvent::class), 'hook');
            },

            // ---- economic victory ----------------------------------------------------

            'stPlunderCheckEconomicVictory: nobody at 7 Renown continues' => function () {
                $game = new StatesTraitGame();
                $game->setPlayerReknown(1, 6);
                $game->setPlayerReknown(2, 3);

                $game->stPlunderCheckEconomicVictory();

                Assert::same([], $game->endOfGameCalls, 'no victory');
                Assert::same(['next'], $game->gamestate->transitions, 'next');
                Assert::contains('No player has achieved an ECONOMIC VICTORY', $this->messages($game), 'logged');
            },

            'stPlunderCheckEconomicVictory: a single player at 7 Renown wins' => function () {
                $game = new StatesTraitGame();
                $game->setPlayerReknown(1, 7);
                $game->setPlayerReknown(2, 6);

                $game->stPlunderCheckEconomicVictory();

                Assert::same([Game::VICTORY_ECONOMIC], $game->endOfGameCalls, 'economic victory');
                Assert::same([], $game->gamestate->transitions, 'the game ends instead of continuing');
                Assert::same('Player One', $game->notify->messages[0]['args']['player_name'], 'winner named');
            },

            'stPlunderCheckEconomicVictory: 7 is the threshold, more also wins' => function () {
                $game = new StatesTraitGame();
                $game->setPlayerReknown(1, 2);
                $game->setPlayerReknown(2, 12);

                $game->stPlunderCheckEconomicVictory();

                Assert::same([Game::VICTORY_ECONOMIC], $game->endOfGameCalls, 'economic victory');
                Assert::same('Player Two', $game->notify->messages[0]['args']['player_name'], 'winner named');
            },

            'stPlunderCheckEconomicVictory: two players at 7+ play another day before day 5' => function () {
                $game = new StatesTraitGame();
                $game->setGameStateValue(Game::DAY, 3);
                $game->setPlayerReknown(1, 8);
                $game->setPlayerReknown(2, 9);

                $game->stPlunderCheckEconomicVictory();

                Assert::same([], $game->endOfGameCalls, 'no immediate victory');
                Assert::same(['next'], $game->gamestate->transitions, 'next');
                Assert::contains('Another day will be played', $this->messages($game), 'logged');
            },

            'stPlunderCheckEconomicVictory: two players at 7+ on day 5 fall through silently to the fifth-day check' => function () {
                $game = new StatesTraitGame();
                $game->setGameStateValue(Game::DAY, 5);
                $game->setPlayerReknown(1, 8);
                $game->setPlayerReknown(2, 9);

                $game->stPlunderCheckEconomicVictory();

                Assert::same([], $game->endOfGameCalls, 'no immediate victory');
                Assert::same(['next'], $game->gamestate->transitions, 'next');
                Assert::notContains('Another day', $this->messages($game), 'no "another day" message on the last day');
            },

            // ---- fifth day -----------------------------------------------------------

            'stPlunderCheckFifthDayVictory: before day 5 the game continues' => function () {
                $game = new StatesTraitGame();
                $game->setGameStateValue(Game::DAY, 4);
                $game->stPlunderCheckFifthDayVictory();
                Assert::same([], $game->endOfGameCalls, 'no victory');
                Assert::same(['next'], $game->gamestate->transitions, 'next');
                Assert::same([], $game->notify->messages, 'silent');
            },

            'stPlunderCheckFifthDayVictory: most Renown wins outright' => function () {
                $game = new StatesTraitGame();
                $game->setGameStateValue(Game::DAY, 5);
                $game->setPlayerReknown(1, 2);
                $game->setPlayerReknown(2, 5);

                $game->stPlunderCheckFifthDayVictory();

                Assert::same([Game::VICTORY_FIFTH_DAY], $game->endOfGameCalls, 'victory');
                Assert::same([], $game->gamestate->transitions, 'game ends');
                Assert::contains('most Renown', $this->messages($game), 'logged');
                Assert::contains('Player Two', json_encode($game->notify->messages), 'winner named');
                Assert::same([], $game->auxScores, 'no tiebreak scoring needed');
            },

            'stPlunderCheckFifthDayVictory: a Renown tie goes to the player controlling more locations' => function () {
                $game = new StatesTraitGame();
                $this->fifthDayTie($game);
                $game->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                $game->theah->setLocationController(Game::LOCATION_CITY_FORUM, 2);
                $game->theah->setLocationController(Game::LOCATION_CITY_BAZAAR, 1);

                $game->stPlunderCheckFifthDayVictory();

                Assert::same([Game::VICTORY_FIFTH_DAY], $game->endOfGameCalls, 'victory');
                Assert::contains('controlling the most locations', $this->messages($game), 'location tiebreak');
                Assert::same([1 => 1000, 2 => 2000], $game->auxScores, 'aux score is 1000 per location');
            },

            'stPlunderCheckFifthDayVictory: a location tie goes to the player with more total Influence' => function () {
                $game = new StatesTraitGame();
                $this->fifthDayTie($game);
                $game->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $game->theah->setLocationController(Game::LOCATION_CITY_FORUM, 2);
                $a = $game->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $game->place(new GenericCharacter('B'), Game::LOCATION_CITY_FORUM, 2);
                $a->ModifiedInfluence = 2;
                $b->ModifiedInfluence = 4;

                $game->stPlunderCheckFifthDayVictory();

                Assert::same([Game::VICTORY_FIFTH_DAY], $game->endOfGameCalls, 'victory');
                Assert::contains('most Influence', $this->messages($game), 'influence tiebreak');
                Assert::same([1 => 1000 + 200, 2 => 1000 + 400], $game->auxScores, 'aux score is locations*1000 + influence*100');
            },

            'stPlunderCheckFifthDayVictory: with no controlled locations the Renown tie falls straight to Influence' => function () {
                $game = new StatesTraitGame();
                $this->fifthDayTie($game);
                $a = $game->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $game->place(new GenericCharacter('B'), Game::LOCATION_CITY_FORUM, 2);
                $a->ModifiedInfluence = 5;
                $b->ModifiedInfluence = 1;

                $game->stPlunderCheckFifthDayVictory();

                Assert::same([Game::VICTORY_FIFTH_DAY], $game->endOfGameCalls, 'victory');
                Assert::contains('NO locations', $this->messages($game), 'reports no locations');
                Assert::contains('most Influence', $this->messages($game), 'influence decides');
                Assert::same([1 => 500, 2 => 100], $game->auxScores, 'only influence scoring');
            },

            'stPlunderCheckFifthDayVictory: an Influence tie goes to the Leader with fewer Wounds' => function () {
                $game = new StatesTraitGame();
                $this->fifthDayTie($game);
                $this->leaders($game, [1 => 3, 2 => 1]);
                $a = $game->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $game->place(new GenericCharacter('B'), Game::LOCATION_CITY_FORUM, 2);
                $a->ModifiedInfluence = 2;
                $b->ModifiedInfluence = 2;

                $game->stPlunderCheckFifthDayVictory();

                Assert::same([Game::VICTORY_FIFTH_DAY], $game->endOfGameCalls, 'victory');
                Assert::contains('least Wounds', $this->messages($game), 'wounds tiebreak');
                Assert::same(200 + (20 - 3), $game->auxScores[1], 'player 1 aux = influence + (20 - wounds)');
                Assert::same(200 + (20 - 1), $game->auxScores[2], 'player 2 aux = influence + (20 - wounds)');
            },

            'stPlunderCheckFifthDayVictory: a destroyed Leader counts as 100 wounds' => function () {
                $game = new StatesTraitGame();
                $this->fifthDayTie($game);
                $leaders = $this->leaders($game, [1 => 0, 2 => 4]);
                $game->discardOrLockerByCardId[$leaders[1]->Id] = true; // player 1's Leader is destroyed
                $a = $game->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $game->place(new GenericCharacter('B'), Game::LOCATION_CITY_FORUM, 2);
                $a->ModifiedInfluence = 2;
                $b->ModifiedInfluence = 2;

                $game->stPlunderCheckFifthDayVictory();

                Assert::same([Game::VICTORY_FIFTH_DAY], $game->endOfGameCalls, 'victory');
                Assert::contains('Player Two', json_encode($game->notify->messages), 'player 2 wins despite more raw wounds');
                Assert::same(200 + (20 - 100), $game->auxScores[1], 'destroyed leader scored as 100 wounds');
                Assert::same(100, $leaders[1]->Wounds, 'destroyed leader recorded at 100 wounds');
            },

            'stPlunderCheckFifthDayVictory: a total tie ends in a shared victory' => function () {
                $game = new StatesTraitGame();
                $this->fifthDayTie($game);
                $this->leaders($game, [1 => 2, 2 => 2]);
                $a = $game->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $game->place(new GenericCharacter('B'), Game::LOCATION_CITY_FORUM, 2);
                $a->ModifiedInfluence = 2;
                $b->ModifiedInfluence = 2;

                $game->stPlunderCheckFifthDayVictory();

                Assert::same([Game::VICTORY_FIFTH_DAY], $game->endOfGameCalls, 'game still ends');
                Assert::contains('shared victory', $this->messages($game), 'shared victory announced');
                Assert::same($game->auxScores[1], $game->auxScores[2], 'identical aux scores');
            },

            // ---- dusk: begin / end / end of day ---------------------------------------

            'stDuskPhaseBegin sets the Dusk phase and queues DuskPhaseBegin' => function () {
                $game = new StatesTraitGame();
                $game->stDuskPhaseBegin();
                Assert::same(Game::DUSK, $game->getGameStateValue(Game::TURN_PHASE), 'phase');
                Assert::count(1, $game->queued(EventDuskPhaseBegin::class), 'event');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stDuskPhaseEnd queues DuskPhaseEnd' => function () {
                $game = new StatesTraitGame();
                $game->stDuskPhaseEnd();
                Assert::count(1, $game->queued(EventDuskPhaseEnd::class), 'event');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stDuskEndOfDay records the crew stat and queues DuskEndOfDay' => function () {
                $game = new StatesTraitGame();
                $game->stDuskEndOfDay();
                Assert::same(1, $game->endOfDayStatCalls, 'stat recorded');
                Assert::count(1, $game->queued(EventDuskEndOfDay::class), 'event');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stDuskEndOfDay discards controlled Brutes: faction Brutes from play, city Brutes to the city discard' => function () {
                $game = new StatesTraitGame();
                $faction = $game->place(new GenericCharacter('Faction Brute', ['Brute']), Game::LOCATION_CITY_DOCKS, 1);
                $city = $game->place(new GenericCityCharacter('Hired Brute', false, ['Mercenary', 'Brute']), Game::LOCATION_CITY_FORUM, 2);
                $normal = $game->place(new GenericCharacter('Normal'), Game::LOCATION_CITY_DOCKS, 1);
                $wild = $game->place(new GenericCityCharacter('Wild Brute', false, ['Mercenary', 'Brute']), Game::LOCATION_CITY_DOCKS, 0);

                $game->stDuskEndOfDay();

                $fromPlay = $game->queued(EventCardDiscardedFromPlay::class);
                Assert::count(1, $fromPlay, 'one faction Brute discarded');
                Assert::same([$faction->Id, 1, Game::LOCATION_CITY_DOCKS], [$fromPlay[0]->cardId, $fromPlay[0]->ownerId, $fromPlay[0]->fromLocation], 'faction brute');

                $toCity = $game->queued(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $toCity, 'one city Brute discarded');
                Assert::same([$city->Id, Game::LOCATION_CITY_FORUM], [$toCity[0]->cardId, $toCity[0]->fromLocation], 'city brute goes to the city discard');
            },

            // ---- dusk: cleanup ---------------------------------------------------------

            'stDuskPhaseCleanup resets location control in globals and on the Theah locations' => function () {
                $game = new StatesTraitGame();
                $this->leaders($game);
                $game->playerRows = [1 => ['player_id' => 1, 'schemeId' => 0], 2 => ['player_id' => 2, 'schemeId' => 0]];
                $game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 1);
                $game->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                $game->stDuskPhaseCleanup();

                Assert::same(0, $game->getControllerForLocation(Game::LOCATION_CITY_DOCKS), 'persisted control reset');
                Assert::same(0, $game->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->Controller, 'in-memory control reset');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stDuskPhaseCleanup sends away characters, readies engaged ones and leaves Home characters in place' => function () {
                $game = new StatesTraitGame();
                $this->leaders($game);
                $game->playerRows = [1 => ['player_id' => 1, 'schemeId' => 0], 2 => ['player_id' => 2, 'schemeId' => 0]];
                $away = $game->place(new GenericCharacter('Away'), Game::LOCATION_CITY_DOCKS, 1);
                $awayTired = $game->place(new GenericCharacter('Away Tired'), Game::LOCATION_CITY_FORUM, 2);
                $awayTired->Engaged = true;
                $home = $game->place(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);
                $homeTired = $game->place(new GenericCharacter('Home Tired'), Game::LOCATION_PLAYER_HOME, 2);
                $homeTired->Engaged = true;
                $merc = $game->place(new GenericCityCharacter('Wild Merc'), Game::LOCATION_CITY_BAZAAR, 0);
                $merc->Engaged = true;

                $game->stDuskPhaseCleanup();

                $moves = $game->queued(EventCardMoving::class);
                Assert::same([$away->Id, $awayTired->Id], array_map(fn($e) => $e->cardId, $moves), 'only away characters move');
                foreach ($moves as $move) {
                    Assert::same(Game::LOCATION_PLAYER_HOME, $move->toLocation, 'moves Home');
                    Assert::false($move->engage, 'moving Home at dusk is free');
                }
                Assert::same(
                    [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM],
                    array_map(fn($e) => $e->fromLocation, $moves),
                    'from their current locations'
                );
                $engarde = array_map(fn($e) => $e->cardId, $game->queued(EventCardEngarded::class));
                Assert::same([$awayTired->Id, $homeTired->Id], $engarde, 'engaged characters readied (not the uncontrolled mercenary)');
            },

            'stDuskPhaseCleanup readies engaged attachments on controlled characters' => function () {
                $game = new StatesTraitGame();
                $this->leaders($game);
                $game->playerRows = [1 => ['player_id' => 1, 'schemeId' => 0], 2 => ['player_id' => 2, 'schemeId' => 0]];
                $holder = $game->place(new GenericCharacter('Holder'), Game::LOCATION_PLAYER_HOME, 1);
                $tired = $game->place(new GenericAttachment('Tired Blade'), Game::LOCATION_PLAYER_HOME, 1);
                $fresh = $game->place(new GenericAttachment('Fresh Blade'), Game::LOCATION_PLAYER_HOME, 1);
                $tired->Engaged = true;
                $holder->Attachments = [$tired->Id, $fresh->Id];

                $game->stDuskPhaseCleanup();

                $engarde = $game->queued(EventCardEngarded::class);
                Assert::count(1, $engarde, 'only the engaged attachment');
                Assert::same($tired->Id, $engarde[0]->cardId, 'engaged attachment readied');
            },

            'stDuskPhaseCleanup discards uncontrolled city cards but keeps controlled ones' => function () {
                $game = new StatesTraitGame();
                $this->leaders($game);
                $game->playerRows = [1 => ['player_id' => 1, 'schemeId' => 0], 2 => ['player_id' => 2, 'schemeId' => 0]];
                $free = $game->place(new GenericCityCharacter('Free Merc'), Game::LOCATION_CITY_BAZAAR, 0);
                $hired = $game->place(new GenericCityCharacter('Hired Merc'), Game::LOCATION_CITY_BAZAAR, 1);
                $plain = $game->place(new GenericCharacter('Not City Deck'), Game::LOCATION_CITY_BAZAAR, 0);

                $game->stDuskPhaseCleanup();

                $discards = $game->queued(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discards, 'one discard');
                Assert::same([$free->Id, Game::LOCATION_CITY_BAZAAR], [$discards[0]->cardId, $discards[0]->fromLocation], 'free merc discarded from the Bazaar');
            },

            'stDuskPhaseCleanup sends chosen schemes to the Locker and clears the player picks' => function () {
                $game = new StatesTraitGame();
                $this->leaders($game);
                $game->playerRows = [
                    1 => ['player_id' => 1, 'schemeId' => 2001],
                    2 => ['player_id' => 2, 'schemeId' => 0],
                ];

                $game->stDuskPhaseCleanup();

                $locker = $game->queued(EventCardSentToLocker::class);
                Assert::count(1, $locker, 'only the player with a scheme');
                Assert::same([1, 2001], [$locker[0]->playerId, $locker[0]->cardId], 'player / scheme');
                Assert::count(1, $game->dbQueries, 'one UPDATE');
                Assert::contains('selected_scheme_id = NULL', $game->dbQueries[0], 'scheme cleared');
                Assert::contains('selected_character_id = NULL', $game->dbQueries[0], 'character cleared');
                Assert::contains('player_id = 1', $game->dbQueries[0], 'for the right player');
            },

            'stDuskPhaseCleanup resets every Leader Panache to its printed value and persists it' => function () {
                $game = new StatesTraitGame();
                $leaders = $this->leaders($game);
                $leaders[1]->Panache = 4;
                $leaders[1]->ModifiedPanache = 7;
                $leaders[2]->Panache = 3;
                $leaders[2]->ModifiedPanache = 1;
                $game->playerRows = [1 => ['player_id' => 1, 'schemeId' => 0], 2 => ['player_id' => 2, 'schemeId' => 0]];

                $game->stDuskPhaseCleanup();

                Assert::same(4, $leaders[1]->ModifiedPanache, 'player 1 reset');
                Assert::same(3, $leaders[2]->ModifiedPanache, 'player 2 reset');
                Assert::same([101, 102], $game->updatedCardObjectIds, 'persisted');
                $notes = array_values(array_filter($game->notify->messages, fn($m) => $m['type'] === 'panacheModified'));
                Assert::same([[1, 4], [2, 3]], array_map(fn($m) => [$m['args']['playerId'], $m['args']['panache']], $notes), 'UI told');
            },

            // ---- dusk: discard down to Panache ------------------------------------------

            'stDuskPhaseDiscard asks only the players holding more cards than their Panache' => function () {
                $game = new StatesTraitGame();
                $leaders = $this->leaders($game);
                $leaders[1]->Panache = 3;
                $leaders[2]->Panache = 3;
                $game->handCounts = [1 => 5, 2 => 3];
                $game->playerRows = [1 => ['leaderId' => 101], 2 => ['leaderId' => 102]];

                $game->stDuskPhaseDiscard();

                Assert::same([[ 'players' => [1], 'transition' => 'cardsDiscarded' ]], $game->gamestate->setPlayersMultiactiveCalls, 'only player 1');
                Assert::contains('Player One', $game->notify->messages[0]['args']['players'], 'named');
            },

            'stDuskPhaseDiscard with every hand within Panache activates nobody' => function () {
                $game = new StatesTraitGame();
                $leaders = $this->leaders($game);
                $leaders[1]->Panache = 5;
                $leaders[2]->Panache = 2;
                $game->handCounts = [1 => 5, 2 => 0];
                $game->playerRows = [1 => ['leaderId' => 101], 2 => ['leaderId' => 102]];

                $game->stDuskPhaseDiscard();

                Assert::same([['players' => [], 'transition' => 'cardsDiscarded']], $game->gamestate->setPlayersMultiactiveCalls, 'empty set, so the state moves on');
                Assert::contains('No players need to discard', $game->notify->messages[0]['message'], 'logged');
            },

            'stDuskPhaseDiscard names every player who must discard' => function () {
                $game = new StatesTraitGame();
                $leaders = $this->leaders($game);
                $leaders[1]->Panache = 1;
                $leaders[2]->Panache = 1;
                $game->handCounts = [1 => 2, 2 => 4];
                $game->playerRows = [1 => ['leaderId' => 101], 2 => ['leaderId' => 102]];

                $game->stDuskPhaseDiscard();

                Assert::same([1, 2], $game->gamestate->setPlayersMultiactiveCalls[0]['players'], 'both');
                Assert::same('Player One, Player Two', $game->notify->messages[0]['args']['players'], 'joined names');
            },

            // ---- dusk: purgatory -> discard ------------------------------------------------

            'stDuskPhaseDiscardEvents discards every card parked in Purgatory then drains the queue' => function () {
                $game = new StatesTraitGame();
                $a = $game->place(new GenericCharacter('Parked A'), Game::LOCATION_PURGATORY, 1);
                $b = $game->place(new GenericCharacter('Parked B'), Game::LOCATION_PURGATORY, 2);
                $game->purgatoryRows = [['id' => $a->Id], ['id' => $b->Id]];

                $game->stDuskPhaseDiscardEvents();

                $events = $game->queued(EventCardDiscardedFromHand::class);
                Assert::same([[1, $a->Id, 0], [2, $b->Id, 0]], array_map(fn($e) => [$e->ownerId, $e->cardId, $e->sourceId], $events), 'owner / card / framework source');
                Assert::same(1, $game->theah->buildCityCalls, 'city rebuilt');
                Assert::same([false], $game->theah->runEventsCalls, 'queue drained with transitions allowed');
                Assert::same([], $game->gamestate->transitions, 'game state leaves via the event queue, not nextState');
            },

            'stDuskPhaseDiscardEvents with an empty Purgatory still drains the queue' => function () {
                $game = new StatesTraitGame();
                $game->stDuskPhaseDiscardEvents();
                Assert::count(0, $game->queued(EventCardDiscardedFromHand::class), 'nothing to discard');
                Assert::same([false], $game->theah->runEventsCalls, 'drained');
            },
        ];
    }
}
