<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd19;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;

class Card_04cd19_Test extends TestCase
{
    public function name(): string
    {
        return '_04cd19 Blood in the Water';
    }

    public function tests(): array
    {
        return [
            'is a catastrophe city event' => function () {
                $card = new _04cd19();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::same(19, $card->CityCardNumber, 'city number');
                Assert::true($card->hasTrait('Catastrophe'), 'Catastrophe');
            },

            // WHY: Reveal uses the event location, not the card's current location,
            // and does not require the card to already be in the city.
            'reveal adds one renown to the event location' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd19(), Game::LOCATION_CITY_FORUM, 0);
                $revealed = new EventCityCardAddedToLocation();
                $revealed->cardId = $card->Id;
                $revealed->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($card, $revealed);

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0];
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown->location, 'event location');
                Assert::same(1, $renown->amount, 'one');
                Assert::same(0, $renown->playerId, 'uncontrolled card');
            },

            'another card being revealed adds no renown' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd19(), Game::LOCATION_CITY_FORUM, 0);
                $revealed = new EventCityCardAddedToLocation();
                $revealed->cardId = 999;
                $revealed->location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($card, $revealed);
                Assert::count(0, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'silent');
            },

            // WHY: The wound shares the engage batch so a later cancel can delete both.
            // A missing batch is minted onto the in-flight engage. Canceled engages,
            // attachments, other locations, and a card outside the city do not wound.
            'engaging a character here wounds them on the same batch' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd19(), Game::LOCATION_CITY_DOCKS, 0);
                $character = $world->placeCharacter(new GenericCharacter('Here'), Game::LOCATION_CITY_DOCKS, 1);
                $attachment = $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd15(), Game::LOCATION_CITY_DOCKS, 1);

                $engaged = new EventCardEngaged();
                $engaged->cardId = $character->Id;
                $world->fireOn($card, $engaged);
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($character->Id, $wound->characterId, 'wounded');
                Assert::same($card->Id, $wound->sourceId, 'source');
                Assert::same((string) $card->Id, $wound->abilityId, 'ability');
                Assert::same(1, $wound->batchId, 'minted batch');
                Assert::same(1, $engaged->batchId, 'stamped on the engage');

                $world->theah->queuedEvents = [];
                $engaged->batchId = 7;
                $engaged->canceled = false;
                $world->fireOn($card, $engaged);
                Assert::same(7, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->batchId, 'existing batch kept');

                $world->theah->queuedEvents = [];
                $engaged->canceled = true;
                $world->fireOn($card, $engaged);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'canceled');

                $engaged->canceled = false;
                $engaged->cardId = $attachment->Id;
                $world->fireOn($card, $engaged);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'attachment');

                $engaged->cardId = $character->Id;
                $character->Location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($card, $engaged);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'elsewhere');

                $character->Location = Game::LOCATION_CITY_DOCKS;
                $card->Location = Game::LOCATION_CITY_DISCARD;
                $world->fireOn($card, $engaged);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'card left the city');
            },
        ];
    }
}
