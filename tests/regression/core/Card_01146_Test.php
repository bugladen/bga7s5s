<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01146;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01146a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01146b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_01146_Test extends TestCase
{
    public function name(): string
    {
        return '_01146 Let The Sword Decide';
    }

    public function tests(): array
    {
        return [
            'constructs Flourish Honor Scheme with both Reactions' => function () {
                $scheme = new _01146();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(63, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Flourish'), 'Flourish');
                Assert::true($scheme->hasTrait('Honor'), 'Honor');
                Assert::count(2, $scheme->getReactions(), 'two reactions');
                Assert::instanceOf(Reaction_01146a::class, $scheme->getReactions()[0], '01146a');
                Assert::instanceOf(Reaction_01146b::class, $scheme->getReactions()[1], '01146b');
            },

            'reaction ids stamped once placed' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01146(), Game::LOCATION_PLAYER_HOME, 1);
                Assert::same($scheme->Id . '_Reaction_01146a', $scheme->getReactions()[0]->Id, 'a');
                Assert::same($scheme->Id . '_Reaction_01146b', $scheme->getReactions()[1]->Id, 'b');
            },

            'resolving adds Renown to Docks and Forum' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01146(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(2, $renown, 'two');
                $locations = array_map(fn($e) => $e->location, $renown);
                Assert::true(in_array(Game::LOCATION_CITY_DOCKS, $locations, true), 'docks');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $locations, true), 'forum');
            },
        ];
    }
}
