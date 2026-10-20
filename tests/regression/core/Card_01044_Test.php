<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01044_Test extends TestCase
{
    public function name(): string
    {
        return '_01044 Armed and Marshaled';
    }

    public function tests(): array
    {
        return [
            'constructs Scheme with Action_01044' => function () {
                $scheme = new _01044();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(37, $scheme->Initiative, 'Initiative');
                Assert::same(-1, $scheme->PanacheModifier, 'PanacheModifier');
                Assert::true($scheme->hasTrait('Duress'), 'Duress');
                Assert::true($scheme->hasTrait('Logistics'), 'Logistics');
                Assert::true($scheme->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Action_01044::class, $scheme->getActions()[0], 'Action_01044');
            },

            'resolve adds renown to Docks and Bazaar and transitions 01044' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01044(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(2, $renown, 'two renown');
                $locations = array_map(fn($e) => $e->location, $renown);
                Assert::true(in_array(Game::LOCATION_CITY_DOCKS, $locations, true), 'docks');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $locations, true), 'bazaar');
                Assert::same('01044', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'state constant registered' => function () {
                Assert::same(2601044, States::PLANNING_PHASE_RESOLVE_SCHEMES_01044, 'scheme state');
                Assert::same(401044, States::HIGH_DRAMA_PLAYER_TURN_01044, 'action state');
            },
        ];
    }
}
