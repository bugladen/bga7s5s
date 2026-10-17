<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd13;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd13;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;

class Card_03cd13_Test extends TestCase
{
    public function name(): string
    {
        return '_03cd13 Crabs in a Bucket';
    }

    public function tests(): array
    {
        return [
            'constructs a Treachery Schadenfreude city event with the claim Action' => function () {
                $card = new _03cd13();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(13, $card->CityCardNumber, 'city number');
                Assert::true($card->hasTrait('Treachery'), 'Treachery');
                Assert::true($card->hasTrait('Schadenfreude'), 'Schadenfreude');
                Assert::count(1, $card->getActions(), 'one Action');
                Assert::instanceOf(Action_03cd13::class, $card->getActions()[0], 'Action_03cd13');
            },

            // WHY: Strictly below the highest Renown. Ties at the top do not draw.
            // All equal, including everyone at 0, draws nobody.
            'reveal draws for everyone below the highest Renown' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd13(), Game::LOCATION_CITY_DOCKS, 0);
                $world->game->playerNames[3] = 'Player Three';
                $world->game->setPlayerReknown(1, 2);
                $world->game->setPlayerReknown(2, 5);
                $world->game->setPlayerReknown(3, 5);

                $added = new EventCityCardAddedToLocation();
                $added->cardId = $card->Id;
                $added->theah = $world->theah;
                $card->handleEvent($added);

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'only player 1');
                Assert::same(1, $draws[0]->playerId, 'player 1');
                Assert::contains($card->getInjectCode(), $draws[0]->reason, 'reason names the card');
            },

            'everyone tied draws nothing' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd13(), Game::LOCATION_CITY_DOCKS, 0);
                $world->game->setPlayerReknown(1, 0);
                $world->game->setPlayerReknown(2, 0);

                $added = new EventCityCardAddedToLocation();
                $added->cardId = $card->Id;
                $added->theah = $world->theah;
                $card->handleEvent($added);

                Assert::count(0, $world->theah->queuedEvents, 'no draws');
            },

            'another card being added does not draw' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd13(), Game::LOCATION_CITY_DOCKS, 0);
                $world->game->setPlayerReknown(2, 4);

                $added = new EventCityCardAddedToLocation();
                $added->cardId = $card->Id + 1;
                $added->theah = $world->theah;
                $card->handleEvent($added);

                Assert::count(0, $world->theah->queuedEvents, 'silent');
            },
        ];
    }
}
