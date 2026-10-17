<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd12;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;

class Card_03cd12_Test extends TestCase
{
    public function name(): string
    {
        return '_03cd12 Equal Claim';
    }

    public function tests(): array
    {
        return [
            'constructs a Bureaucracy Squabble Compromise city event' => function () {
                $card = new _03cd12();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::same(12, $card->CityCardNumber, 'city number');
                Assert::true($card->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($card->hasTrait('Squabble'), 'Squabble');
                Assert::true($card->hasTrait('Compromise'), 'Compromise');
            },

            // WHY: Counts are per seated player at this location only. Home characters
            // do not count. Equal zeros still match, so an empty controlled location falls.
            'equal counts, including both zero, uncontrol the location' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd12(), Game::LOCATION_CITY_DOCKS, 0);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('HomeOnly'), Game::LOCATION_PLAYER_HOME, 1);

                $end = new EventHighDramaPhaseEnd();
                $end->theah = $world->theah;
                $card->handleEvent($end);

                $event = $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class);
                Assert::count(1, $event, 'uncontrolled');
                Assert::same(2, $event[0]->playerId, 'previous controller');
                Assert::same(Game::LOCATION_CITY_DOCKS, $event[0]->location, 'docks');
            },

            'one character each is equal' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd12(), Game::LOCATION_CITY_FORUM, 0);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_FORUM, 2);

                $end = new EventHighDramaPhaseEnd();
                $end->theah = $world->theah;
                $card->handleEvent($end);

                Assert::count(1, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class), 'uncontrolled');
            },

            'unequal counts leave the controller alone' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd12(), Game::LOCATION_CITY_DOCKS, 0);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('A2'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);

                $end = new EventHighDramaPhaseEnd();
                $end->theah = $world->theah;
                $card->handleEvent($end);

                Assert::count(0, $world->theah->queuedEvents, 'still controlled');
            },

            // WHY: A third seat with zero breaks a 1-1 tie. Uncontrolled characters
            // (controller 0) are not a seat and do not change anyone's count.
            'a third player with zero is not equal to one-and-one' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd12(), Game::LOCATION_CITY_DOCKS, 0);
                $world->game->playerNames[3] = 'Player Three';
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Street'), Game::LOCATION_CITY_DOCKS, 0);

                $end = new EventHighDramaPhaseEnd();
                $end->theah = $world->theah;
                $card->handleEvent($end);

                Assert::count(0, $world->theah->queuedEvents, '3 has zero');
            },

            'an already uncontrolled location is left alone' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd12(), Game::LOCATION_CITY_DOCKS, 0);

                $end = new EventHighDramaPhaseEnd();
                $end->theah = $world->theah;
                $card->handleEvent($end);

                Assert::count(0, $world->theah->queuedEvents, 'already free');
            },

            'a location that cannot become uncontrolled stays controlled' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd12(), Game::LOCATION_CITY_DOCKS, 0);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBecomeUncontrolled = false;

                $end = new EventHighDramaPhaseEnd();
                $end->theah = $world->theah;
                $card->handleEvent($end);

                Assert::count(0, $world->theah->queuedEvents, 'blocked');
            },

            'the card must be in the city' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd12(), Game::LOCATION_CITY_DISCARD, 0);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                $end = new EventHighDramaPhaseEnd();
                $end->theah = $world->theah;
                $card->handleEvent($end);

                Assert::count(0, $world->theah->queuedEvents, 'not in the city');
            },
        ];
    }
}
