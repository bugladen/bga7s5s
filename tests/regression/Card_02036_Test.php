<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02036a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02036b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_02036_Test extends TestCase
{
    public function name(): string
    {
        return '_02036 Rumors of the Crimson Roger';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Terror Rumor Scheme with two Pirate Actions' => function () {
                $scheme = new _02036();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(39, $scheme->Initiative, 'Initiative');
                Assert::same(-1, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasFaction('Castille'), 'Castille');
                Assert::true($scheme->hasTrait('Terror'), 'Terror');
                Assert::true($scheme->hasTrait('Rumor'), 'Rumor');
                Assert::count(2, $scheme->getActions(), 'two');
                Assert::instanceOf(Action_02036a::class, $scheme->getActions()[0], 'a');
                Assert::instanceOf(Action_02036b::class, $scheme->getActions()[1], 'b');
            },

            'owns both actions when placed' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _02036(), Game::LOCATION_PLAYER_HOME, 1);
                Assert::same($scheme->Id, $scheme->getActions()[0]->OwnerId, 'a');
                Assert::same($scheme->Id, $scheme->getActions()[1]->OwnerId, 'b');
            },

            // Resolve always adds two Renown to City Docks.
            'resolve queues two Renown on City Docks' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _02036(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'one event');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'docks');
                Assert::same(2, $added[0]->amount, 'two');
                Assert::same(1, $added[0]->playerId, 'player');
            },

            'ignores resolve of a different scheme' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _02036(), Game::LOCATION_PLAYER_HOME, 1);
                $other = $world->placeCard(new _02036(), Game::LOCATION_PLAYER_HOME, 2);

                $event = new EventResolveScheme();
                $event->scheme = $other;
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },
        ];
    }
}
