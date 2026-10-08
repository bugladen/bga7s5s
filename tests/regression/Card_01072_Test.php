<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01072_Test extends TestCase
{
    public function name(): string
    {
        return '_01072 Réputation Méritée';
    }

    private function allLocationsRenowned(TestWorld $world): void
    {
        foreach ($world->theah->getCityLocations() as $name => $_) {
            $world->theah->setLocationRenown($name, 1);
        }
    }

    public function tests(): array
    {
        return [
            'constructs Scheme with Action_01072' => function () {
                $scheme = new _01072();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(62, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'PanacheModifier');
                Assert::true($scheme->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::true($scheme->hasTrait('Honor'), 'Honor');
                Assert::true($scheme->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Action_01072::class, $scheme->getActions()[0], 'Action_01072');
            },

            'resolve transitions 01072 at medium priority' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01072(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01072', $transitions[0]->transition, 'name');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'priority');
            },

            'resolve of another scheme is ignored' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01072(), Game::LOCATION_PLAYER_HOME, 1);
                $other = $world->placeCard(new _01072(), Game::LOCATION_PLAYER_HOME, 2);

                $event = new EventResolveScheme();
                $event->scheme = $other;
                $event->playerId = 2;
                $event->playerName = 'Player Two';
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedEvents, 'not ours');
            },

            // --- When Revealed: only 0-Renown locations ---

            'choose adds 1 Renown to a location with no Renown' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01072(), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01072,
                    'planningPhaseResolveSchemes_01072',
                    '01072',
                    [Game::LOCATION_CITY_FORUM]
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'renown');
                Assert::same(Game::LOCATION_CITY_FORUM, $renown[0]->location, 'forum');
                Assert::same(1, $renown[0]->amount, 'amount');
                Assert::same(1, $renown[0]->playerId, 'controller');
                Assert::same([''], $world->game->gamestate->transitions, 'nextState ""');
            },

            // WHY: Card text restricts to "a location with no Renown".
            'choose refuses a location that already has Renown' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01072(), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01072,
                        'x',
                        '01072',
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
                Assert::count(0, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'no renown');
            },

            'pass is refused while a 0-Renown location exists' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01072(), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                $threw = false;
                try {
                    $scheme->actFromCardPass($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_01072, 'x', '01072');
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'cannot pass');
                Assert::count(0, $world->game->gamestate->transitions, 'no nextState');
            },

            'pass is allowed when every location has Renown' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01072(), Game::LOCATION_PLAYER_HOME, 1);
                $this->allLocationsRenowned($world);

                $scheme->actFromCardPass($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_01072, 'x', '01072');

                Assert::same([''], $world->game->gamestate->transitions, 'nextState ""');
            },

            'state constant registered' => function () {
                Assert::same(2601072, States::PLANNING_PHASE_RESOLVE_SCHEMES_01072, 'scheme state');
                Assert::same(401072, States::HIGH_DRAMA_PLAYER_TURN_01072, 'action state');
                Assert::same(4010722, States::HIGH_DRAMA_PLAYER_TURN_01072_2, 'action step 2');
            },
        ];
    }
}
