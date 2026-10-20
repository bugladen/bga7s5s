<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02004;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_02004_Test extends TestCase
{
    public function name(): string
    {
        return '_02004 Crash the Party';
    }

    public function tests(): array
    {
        return [
            'constructs Vodacce Scheme with Reaction_02004' => function () {
                $scheme = new _02004();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(12, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Ad Hoc'), 'Ad Hoc');
                Assert::true($scheme->hasTrait('Unwelcome'), 'Unwelcome');
                Assert::true($scheme->hasFaction('Vodacce'), 'Vodacce');
                Assert::instanceOf(Reaction_02004::class, $scheme->getReactions()[0], 'Reaction');
            },

            'owns Reaction when placed' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _02004(), Game::LOCATION_PLAYER_HOME, 1);
                Assert::same($scheme->Id, $scheme->getReactions()[0]->OwnerId, 'owner');
            },

            // WHY: same renown pattern as Armed and Marshaled (_01044) — Docks + Bazaar.
            'resolve adds renown to Docks and Bazaar' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _02004(), Game::LOCATION_PLAYER_HOME, 1);

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
            },
        ];
    }
}
