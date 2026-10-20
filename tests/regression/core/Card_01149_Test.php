<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01149;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01149;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

final class TestCityCard_01149 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'Top City Card';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->resetCard();
    }
}

class Card_01149_Test extends TestCase
{
    public function name(): string
    {
        return '_01149 Midnight Shipment';
    }

    public function tests(): array
    {
        return [
            'constructs Logistics Market Scheme with Action_01149' => function () {
                $scheme = new _01149();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(80, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Logistics'), 'Logistics');
                Assert::true($scheme->hasTrait('Market'), 'Market');
                Assert::instanceOf(Action_01149::class, $scheme->getActions()[0], 'Action_01149');
            },

            'resolving adds Renown and queues top city card to Docks' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01149(), Game::LOCATION_PLAYER_HOME, 1);
                $top = $world->placeCard(new TestCityCard_01149(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $top->Id]];

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

                $adds = $world->theah->queuedOfType(EventCityCardAddedToLocation::class);
                Assert::count(1, $adds, 'city card');
                Assert::same($top->Id, $adds[0]->cardId, 'top');
                Assert::same(Game::LOCATION_CITY_DOCKS, $adds[0]->location, 'to docks');
            },

            'state constant registered' => function () {
                Assert::same(401149, States::HIGH_DRAMA_PLAYER_TURN_01149, 'action');
            },
        ];
    }
}
