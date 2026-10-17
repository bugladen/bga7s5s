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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02046;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_02046_Test extends TestCase
{
    public function name(): string
    {
        return "_02046 Winter's Wind";
    }

    /** @return _02046 */
    private function scene(TestWorld $world): _02046
    {
        /** @var _02046 $scheme */
        $scheme = $world->placeCard(new _02046(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Demoralize Scheme' => function () {
                $scheme = new _02046();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::same(44, $scheme->Initiative, 'Initiative');
                Assert::same(1, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasFaction('Ussura'), 'Ussura');
                Assert::true($scheme->hasTrait("Tură's Touch"), 'touch');
                Assert::true($scheme->hasTrait('Demoralize'), 'Demoralize');
            },

            'state constants registered' => function () {
                Assert::same(2602046, States::PLANNING_PHASE_RESOLVE_SCHEMES_02046, 'first');
                Assert::same(26020462, States::PLANNING_PHASE_RESOLVE_SCHEMES_02046_2, 'second');
            },

            'resolve queues the first Renown chooser' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02046', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
                Assert::count(0, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'not yet');
            },

            'ignores resolve of a different scheme' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $other = $world->placeCard(new _02046(), Game::LOCATION_PLAYER_HOME, 2);

                $event = new EventResolveScheme();
                $event->scheme = $other;
                $event->playerId = 2;
                $event->playerName = 'Player Two';
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },

            'first location gains a Renown and opens an optional second location' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02046,
                    'planningPhaseResolveSchemes_02046',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_DOCKS]
                );

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'one');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'docks');
                Assert::same(1, $added[0]->amount, 'amount');
                Assert::same(1, $added[0]->playerId, 'player');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->game->globals->get(Game::CHOSEN_LOCATION), 'remembered');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'second');
                Assert::same('02046_2', $transitions[0]->transition, 'name');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'second step args publish the location already chosen' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02046_2,
                    'planningPhaseResolveSchemes_02046_2',
                    (string) $scheme->Id
                );

                Assert::same(Game::LOCATION_CITY_FORUM, $args['chosenLocation'], 'chosen');
            },

            'a different second location gains a Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02046_2,
                    'planningPhaseResolveSchemes_02046_2',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_FORUM]
                );

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'one');
                Assert::same(Game::LOCATION_CITY_FORUM, $added[0]->location, 'forum');
                Assert::same([''], $world->game->gamestate->transitions, 'done');
            },

            'the second location cannot be the first' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02046_2,
                        'planningPhaseResolveSchemes_02046_2',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'same location');
                Assert::count(0, $world->theah->queuedEvents, 'none');
            },

            'passing the optional second Renown ends the resolve' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $scheme->actFromCardPass(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02046_2,
                    'planningPhaseResolveSchemes_02046_2',
                    (string) $scheme->Id
                );

                Assert::same([''], $world->game->gamestate->transitions, 'done');
                Assert::count(0, $world->theah->queuedEvents, 'no second renown');
            },

            // WHY: Recruit only happens in High Drama, so it engages with no phase check.
            'a recruited character is engaged' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->game->gamestate->currentMainStateId = 2602046;
                $recruit = $world->placeCharacter(new GenericCharacter('Recruit'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventCharacterRecruited();
                $event->characterId = $recruit->Id;
                $event->playerId = 2;
                $world->fireOn($scheme, $event);

                $engaged = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engaged, 'engaged');
                Assert::same($recruit->Id, $engaged[0]->cardId, 'who');
                Assert::same($scheme->ControllerId, $engaged[0]->playerId, 'scheme controller');
                Assert::same($scheme->Id, $engaged[0]->sourceId, 'source');
            },

            // WHY: Muster can happen outside High Drama. The gate is the main state id prefix 30–69.
            'muster engages only during High Drama' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $character = $world->placeCharacter(new GenericCharacter('Mustered'), Game::LOCATION_CITY_DOCKS, 1);

                $muster = function () use ($world, $character): EventCharacterMustered {
                    $event = new EventCharacterMustered();
                    $event->characterId = $character->Id;
                    $event->playerId = 1;
                    $event->theah = $world->theah;
                    return $event;
                };

                $world->game->gamestate->currentMainStateId = 2602046;
                $scheme->handleEvent($muster());
                Assert::count(0, $world->theah->queuedEvents, 'planning');

                $world->game->gamestate->currentMainStateId = 402047;
                $scheme->handleEvent($muster());
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'high drama');

                $world->theah->takeQueuedEvents();
                $world->game->gamestate->currentMainStateId = 300000;
                $scheme->handleEvent($muster());
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'prefix 30');

                $world->theah->takeQueuedEvents();
                $world->game->gamestate->currentMainStateId = 700000;
                $scheme->handleEvent($muster());
                Assert::count(0, $world->theah->queuedEvents, 'prefix 70');
            },
        ];
    }
}
