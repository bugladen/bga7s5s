<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01148;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01148;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_01148_Test extends TestCase
{
    public function name(): string
    {
        return '_01148 Marooned';
    }

    public function tests(): array
    {
        return [
            'constructs Betrayal Solitary Scheme with Action_01148' => function () {
                $scheme = new _01148();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(22, $scheme->Initiative, 'Initiative');
                Assert::same(1, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Betrayal'), 'Betrayal');
                Assert::true($scheme->hasTrait('Solitary'), 'Solitary');
                Assert::instanceOf(Action_01148::class, $scheme->getActions()[0], 'Action_01148');
            },

            'resolving adds Renown to Docks and Bazaar' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01148(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(2, $renown, 'two');
                $locations = array_map(fn($e) => $e->location, $renown);
                Assert::true(in_array(Game::LOCATION_CITY_DOCKS, $locations, true), 'docks');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $locations, true), 'bazaar');
            },

            'state constants registered' => function () {
                Assert::same(401148, States::HIGH_DRAMA_PLAYER_TURN_01148, 'choose merc');
                Assert::same(4011482, States::HIGH_DRAMA_PLAYER_TURN_01148_2, 'hand gate');
                Assert::same(4011483, States::HIGH_DRAMA_PLAYER_TURN_01148_3, 'discard');
                Assert::same(4011484, States::HIGH_DRAMA_PLAYER_TURN_01148_4, 'engage/wound');
            },
        ];
    }
}
