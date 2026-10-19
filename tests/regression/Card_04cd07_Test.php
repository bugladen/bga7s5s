<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd07;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class Card_04cd07_Test extends TestCase
{
    public function name(): string
    {
        return '_04cd07 Festival of Fools';
    }

    public function tests(): array
    {
        return [
            'is a revelry city event' => function () {
                $card = new _04cd07();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::same(7, $card->CityCardNumber, 'city number');
                Assert::true($card->hasTrait('Revelry'), 'Revelry');
            },

            'reveal draws one card for every seated player' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd07(), Game::LOCATION_CITY_FORUM, 0);
                $revealed = new EventCityCardAddedToLocation();
                $revealed->cardId = $card->Id;
                $world->fireOn($card, $revealed);

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(2, $draws, 'both players');
                Assert::same(1, $draws[0]->playerId, 'first');
                Assert::same(2, $draws[1]->playerId, 'second');
            },

            'another card being revealed does not draw' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd07(), Game::LOCATION_CITY_FORUM, 0);
                $revealed = new EventCityCardAddedToLocation();
                $revealed->cardId = 999;
                $world->fireOn($card, $revealed);
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'silent');
            },

            // WHY: The controller is CityLocation->Controller. Uncontrolled (0) skips nobody.
            // A character at Home is not "here".
            'end of high drama draws for players here who do not control the location' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd07(), Game::LOCATION_CITY_FORUM, 0);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('Theirs'), Game::LOCATION_CITY_FORUM, 2);
                $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 2);

                $end = new EventHighDramaPhaseEnd();
                $world->fireOn($card, $end);
                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'only the visitor');
                Assert::same(2, $draws[0]->playerId, 'player two');

                $world->theah->queuedEvents = [];
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 0);
                $world->fireOn($card, $end);
                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::same([1, 2], array_map(fn($draw) => $draw->playerId, $draws), 'nobody controls it');
            },

            'end of high drama is silent once the card has left the city' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd07(), Game::LOCATION_CITY_DISCARD, 0);
                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DISCARD, 1);
                $end = new EventHighDramaPhaseEnd();
                $world->fireOn($card, $end);
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'discarded');
            },
        ];
    }
}
