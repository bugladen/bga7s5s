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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02045;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_02045_Test extends TestCase
{
    public function name(): string
    {
        return '_02045 Path to Poluchatel';
    }

    /** @return _02045 */
    private function scene(TestWorld $world): _02045
    {
        /** @var _02045 $scheme */
        $scheme = $world->placeCard(new _02045(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Dar Matushki Scheme with a Sorcerer Action' => function () {
                $scheme = new _02045();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(25, $scheme->Initiative, 'Initiative');
                Assert::same(1, $scheme->PanacheModifier, 'Panache');
                Assert::same('', $scheme->ChosenLocation, 'unset');
                Assert::true($scheme->hasFaction('Ussura'), 'Ussura');
                Assert::true($scheme->hasTrait('Dar Matushki'), 'Dar Matushki');
                Assert::true($scheme->hasTrait('Discovery'), 'Discovery');
                Assert::instanceOf(Action_02045::class, $scheme->getActions()[0], 'action');
            },

            'state constants registered' => function () {
                Assert::same(2602045, States::PLANNING_PHASE_RESOLVE_SCHEMES_02045, 'outer');
                Assert::same(26020452, States::PLANNING_PHASE_RESOLVE_SCHEMES_02045_2, 'renown');
            },

            'property array publishes the chosen location' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;
                $props = $scheme->getPropertyArray($world->game);
                Assert::same(Game::LOCATION_CITY_DOCKS, $props['chosenLocation'], 'location');
            },

            'resolve queues the outermost-location chooser' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02045', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            'ignores resolve of a different scheme' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $other = $world->placeCard(new _02045(), Game::LOCATION_PLAYER_HOME, 2);

                $event = new EventResolveScheme();
                $event->scheme = $other;
                $event->playerId = 2;
                $event->playerName = 'Player Two';
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },

            // WHY: Two players: outermost are the Docks and the Bazaar. The Forums are not.
            'choosing an outermost location stores it and advances' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                Assert::same(
                    [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR],
                    $world->theah->getOuterCityLocations(),
                    'two-player outer'
                );

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02045,
                    'planningPhaseResolveSchemes_02045',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_DOCKS]
                );

                Assert::same(Game::LOCATION_CITY_DOCKS, $scheme->ChosenLocation, 'chosen');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->game->globals->get(Game::CHOSEN_LOCATION), 'global');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'next');
            },

            // WHY: Three players swap the outer pair. Docks are legal at two players and illegal at three.
            'three players use Ole\'s Inn and the Bazaar as the outer locations' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->game->playerCount = 3;

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02045,
                        'planningPhaseResolveSchemes_02045',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'docks not outer');
                Assert::same('', $scheme->ChosenLocation, 'unchanged');

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02045,
                    'planningPhaseResolveSchemes_02045',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_OLES_INN]
                );
                Assert::same(Game::LOCATION_CITY_OLES_INN, $scheme->ChosenLocation, 'oles');
            },

            'refuses a location that is not outermost' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02045,
                        'planningPhaseResolveSchemes_02045',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_FORUM]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'forum');
                Assert::same('', $scheme->ChosenLocation, 'unset');
            },

            'second step args remember the chosen location' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02045_2,
                    'planningPhaseResolveSchemes_02045_2',
                    (string) $scheme->Id
                );

                Assert::same(Game::LOCATION_CITY_DOCKS, $args['chosenLocation'], 'chosen');
            },

            'two other locations each gain a Renown and the scheme moves there' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02045_2,
                    'planningPhaseResolveSchemes_02045_2',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_BAZAAR]
                );

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(2, $added, 'two');
                Assert::same(Game::LOCATION_CITY_FORUM, $added[0]->location, 'forum');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $added[1]->location, 'bazaar');
                Assert::same(1, $added[0]->amount, 'one');
                Assert::same(1, $added[0]->playerId, 'player');

                $moved = $world->theah->queuedOfType(EventSchemeMovedToCity::class);
                Assert::count(1, $moved, 'move');
                Assert::same($scheme, $moved[0]->scheme, 'scheme');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moved[0]->location, 'docks');
                Assert::same(['locationsChosen'], $world->game->gamestate->transitions, 'next');
            },

            'refuses one location, a duplicate, or the chosen location' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;
                $state = States::PLANNING_PHASE_RESOLVE_SCHEMES_02045_2;

                foreach ([
                    [Game::LOCATION_CITY_FORUM],
                    [Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_FORUM],
                    [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM],
                ] as $ids) {
                    $threw = false;
                    try {
                        $scheme->actFromCardWithIds($world->game, $state, 'planningPhaseResolveSchemes_02045_2', (string) $scheme->Id, $ids);
                    } catch (UserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, 'rejected');
                }
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'moving to the city notifies with the location' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $event = $world->theah->createEvent(Events::SchemeMovedToCity);
                Assert::instanceOf(EventSchemeMovedToCity::class, $event, 'type');
                $event->scheme = $scheme;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);

                $notes = array_values(array_filter(
                    $world->game->notify->messages,
                    fn ($m) => $m['type'] === 'schemeMovedToCity'
                ));
                Assert::count(1, $notes, 'notify');
                Assert::same($scheme->Id, $notes[0]['args']['cardId'], 'card');
                Assert::same(Game::LOCATION_CITY_DOCKS, $notes[0]['args']['location'], 'location');
            },
        ];
    }
}
