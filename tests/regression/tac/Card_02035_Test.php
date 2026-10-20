<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02035;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_02035_Test extends TestCase
{
    public function name(): string
    {
        return '_02035 Castillian Caper';
    }

    /** @return _02035 */
    private function scene(TestWorld $world): _02035
    {
        /** @var _02035 $scheme */
        $scheme = $world->placeCard(new _02035(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Castille Heist Crime Scheme with a Scoundrel Action' => function () {
                $scheme = new _02035();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(93, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasFaction('Castille'), 'Castille');
                Assert::true($scheme->hasTrait('Heist'), 'Heist');
                Assert::true($scheme->hasTrait('Crime'), 'Crime');
                Assert::instanceOf(Action_02035::class, $scheme->getActions()[0], 'action');
            },

            'state constant registered' => function () {
                Assert::same(2602035, States::PLANNING_PHASE_RESOLVE_SCHEMES_02035, 'resolve');
            },

            // WHY: Resolve offers a chooser when at least one city location has no Renown.
            'resolve queues a location chooser when empty locations exist' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02035', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            'resolve places nothing when every city location already has Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                foreach ($world->theah->getCityLocations() as $location) {
                    $world->theah->setLocationRenown($location->Name, 1);
                }

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no chooser');
                Assert::count(0, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'no renown');
            },

            'ignores resolve of a different scheme' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $other = $world->placeCard(new _02035(), Game::LOCATION_PLAYER_HOME, 2);

                $event = new EventResolveScheme();
                $event->scheme = $other;
                $event->playerId = 2;
                $event->playerName = 'Player Two';
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },

            // Up to two different empty locations. Five start empty, so two are required.
            'args require two different empty locations' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02035,
                    'planningPhaseResolveSchemes_02035',
                    (string) $scheme->Id
                );

                Assert::same(2, $args['requiredLocationCount'], 'two');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'docks excluded');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'forum');
            },

            'args require the only remaining empty location' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                foreach ($world->theah->getCityLocations() as $location) {
                    if ($location->Name !== Game::LOCATION_CITY_FORUM) {
                        $world->theah->setLocationRenown($location->Name, 1);
                    }
                }

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02035,
                    'planningPhaseResolveSchemes_02035',
                    (string) $scheme->Id
                );

                Assert::same(1, $args['requiredLocationCount'], 'one');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'forum');
            },

            'placing Renown on two empty locations queues +1 each' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02035,
                    'planningPhaseResolveSchemes_02035',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM]
                );

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(2, $added, 'two');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'docks');
                Assert::same(Game::LOCATION_CITY_FORUM, $added[1]->location, 'forum');
                Assert::same(1, $added[0]->amount, 'one');
                Assert::same(1, $added[1]->playerId, 'player');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'refuses fewer locations than required' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02035,
                        'planningPhaseResolveSchemes_02035',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'need two');
            },

            'refuses the same location twice' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02035,
                        'planningPhaseResolveSchemes_02035',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'duplicates');
            },

            'refuses a location that already has Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02035,
                        'planningPhaseResolveSchemes_02035',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'has renown');
            },
        ];
    }
}
