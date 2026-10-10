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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01145;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01145_Test extends TestCase
{
    public function name(): string
    {
        return '_01145 Inspire Generosity';
    }

    private function revealed(TestWorld $world): _01145
    {
        /** @var _01145 $scheme */
        $scheme = $world->placeCard(new _01145(), Game::LOCATION_PLAYER_HOME, 1);
        $world->game->activePlayerId = 1;
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Bureaucracy Camaraderie Scheme' => function () {
                $scheme = new _01145();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::same(15, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($scheme->hasTrait('Camaraderie'), 'Camaraderie');
            },

            'resolving queues the 01145 transition at medium priority' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01145', $transitions[0]->transition, 'name');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            'step 1 refuses an empty location choice' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01145,
                        'x',
                        '',
                        []
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
            },

            'step 1 refuses a location with no Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01145,
                        'x',
                        '',
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'no Renown');
            },

            'step 1 records the from-location and transitions locationChosen' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01145,
                    'x',
                    '',
                    [Game::LOCATION_CITY_DOCKS]
                );

                Assert::same(Game::LOCATION_CITY_DOCKS, $world->game->globals->get(Game::CHOSEN_LOCATION), 'from');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'next');
            },

            'pass is refused while any location has Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                $threw = false;
                try {
                    $scheme->actFromCardPass(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01145,
                        'x',
                        ''
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'cannot pass');
            },

            'pass is allowed when every city location has zero Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $scheme->actFromCardPass(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01145,
                    'x',
                    ''
                );

                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass');
            },

            'args for step 2 expose the chosen from-location' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_2,
                    'planningPhaseResolveSchemes_01145_2',
                    ''
                );

                Assert::same(Game::LOCATION_CITY_FORUM, $args['chosenLocation'], 'chosen');
            },

            'step 2 refuses the same location as the source' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_2,
                        'x',
                        '',
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'same location');
            },

            'step 2 queues move/remove/add Renown batch and stores the destination' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_2,
                    'x',
                    '',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                Assert::count(1, $moving, 'moving');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moving[0]->toLocation, 'to');

                Assert::count(1, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'removed');
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'added');
                Assert::same(Game::LOCATION_CITY_FORUM, $added[0]->location, 'to Forum');
                Assert::same(Game::LOCATION_CITY_FORUM, $world->game->globals->get(Game::CHOSEN_CARD), 'dest stored');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'next');
            },

            // WHY: step 3 simulates post-move Renown for "locations that have none", then draws.
            'state step 3 tops up empty locations and draws for each player' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $world->game->globals->set(Game::CHOSEN_CARD, Game::LOCATION_CITY_FORUM);
                // Pre-move board: Docks 1, Forum 0, others 0. After move Docks 0 / Forum 1 —
                // empty locations (Docks + Bazaar + Inn + Garden) each get +1.
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 0);
                $world->game->playerScores = [1 => 3, 2 => 5];
                // Player 1 unique fewest characters (only scheme controller has none in play).
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_BAZAAR, 2);

                $scheme->stateFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_3,
                    'planningPhaseResolveSchemes_01145_3',
                    ''
                );

                $topUps = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                $topUpLocations = array_map(fn($e) => $e->location, $topUps);
                Assert::true(in_array(Game::LOCATION_CITY_DOCKS, $topUpLocations, true), 'Docks emptied by move');
                Assert::false(in_array(Game::LOCATION_CITY_FORUM, $topUpLocations, true), 'Forum has Renown after move');

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                // Each player draws once, plus unique least Renown (p1), plus unique fewest characters (p1).
                Assert::true(count($draws) >= 4, 'base draws + bonuses: ' . count($draws));
                $drawPlayers = array_map(fn($e) => $e->playerId, $draws);
                Assert::true(in_array(1, $drawPlayers, true), 'p1 draws');
                Assert::true(in_array(2, $drawPlayers, true), 'p2 draws');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'state step 3 skips least-Renown bonus draw when Renown is tied' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $world->game->globals->set(Game::CHOSEN_CARD, Game::LOCATION_CITY_FORUM);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $world->game->playerScores = [1 => 2, 2 => 2];
                // Unique fewest characters: player 1 (0) vs player 2 (1).
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_BAZAAR, 2);

                $scheme->stateFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_3,
                    'x',
                    ''
                );

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                // 2 base (each player) + 0 least-Renown (tie) + 1 fewest characters = 3.
                Assert::count(3, $draws, 'no least-Renown bonus on tie');
            },
        ];
    }
}
