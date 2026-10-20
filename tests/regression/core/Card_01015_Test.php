<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01015;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01015;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01015;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_01015_Test extends TestCase
{
    public function name(): string
    {
        return '_01015 The Great Game';
    }

    public function tests(): array
    {
        return [
            'constructs Scheme with Action_01015 and Reaction_01015' => function () {
                $scheme = new _01015();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(60, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'PanacheModifier');
                Assert::true($scheme->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($scheme->hasTrait('Zeal'), 'Zeal');
                Assert::true($scheme->hasFaction('Vodacce'), 'Faction');
                Assert::instanceOf(Action_01015::class, $scheme->getActions()[0], 'Action_01015');
                Assert::instanceOf(Reaction_01015::class, $scheme->getReactions()[0], 'Reaction_01015');
            },

            'resolve adds renown to Docks and Grand Bazaar' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);

                $adds = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(2, $adds, 'two renown adds');
                $locations = array_map(fn($e) => $e->location, $adds);
                Assert::true(in_array(Game::LOCATION_CITY_DOCKS, $locations, true), 'Docks');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $locations, true), 'Bazaar');
                foreach ($adds as $add) {
                    Assert::same(1, $add->amount, 'amount 1');
                }
            },

            'state constant registered' => function () {
                Assert::same(401015, States::HIGH_DRAMA_PLAYER_TURN_01015, 'state id');
            },
        ];
    }
}
