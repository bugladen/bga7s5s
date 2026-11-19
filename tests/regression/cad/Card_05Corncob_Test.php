<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Corncob;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;

class Card_05Corncob_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Corncob';
    }

    public function tests(): array
    {
        return [
            'constructs as an Eisen Scoundrel with Action and Covert Technique' => function () {
                $card = new _05Corncob();
                Assert::same('Eisen', $card->Factions[0], 'faction');
                Assert::true($card->hasTrait('Insimore'), 'Insimore');
                Assert::count(1, $card->getActions(), 'action');
                Assert::count(1, $card->getTechniques(), 'technique');
            },

            // WHY: Forced mills each faction deck AND the City Deck. Still-in-deck gate keeps
            // FakeGame's shared topFactionCards from re-milling into player 2.
            'Forced at end of Planning discards the top 3 cards of each faction deck and the City Deck' => function () {
                $world = new TestWorld();
                $corncob = $world->placeCharacter(new _05Corncob(), Game::LOCATION_CITY_DOCKS, 1);
                $deck = $world->game->getPlayerFactionDeckName(1);
                $discard = $world->game->getPlayerDiscardDeckName(1);

                $a = $world->placeCard(new GenericCharacter('A'), $deck, 1);
                $b = $world->placeCard(new GenericCharacter('B'), $deck, 1);
                $c = $world->placeCard(new GenericCharacter('C'), $deck, 1);
                $world->game->topFactionCards = [
                    ['id' => $a->Id],
                    ['id' => $b->Id],
                    ['id' => $c->Id],
                ];

                $cityA = $world->placeCard(new GenericCharacter('CityA'), Game::LOCATION_CITY_DECK, 0);
                $cityB = $world->placeCard(new GenericCharacter('CityB'), Game::LOCATION_CITY_DECK, 0);
                $cityC = $world->placeCard(new GenericCharacter('CityC'), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [
                    ['id' => $cityA->Id],
                    ['id' => $cityB->Id],
                    ['id' => $cityC->Id],
                ];

                $event = new EventPhasePlanningEnd();
                $world->fireOn($corncob, $event);

                Assert::same($discard, $a->Location, 'a milled');
                Assert::same($discard, $b->Location, 'b milled');
                Assert::same($discard, $c->Location, 'c milled');
                Assert::same(Game::LOCATION_CITY_DISCARD, $cityA->Location, 'city a');
                Assert::same(Game::LOCATION_CITY_DISCARD, $cityB->Location, 'city b');
                Assert::same(Game::LOCATION_CITY_DISCARD, $cityC->Location, 'city c');
            },

            'Forced skips when Cobb is in the locker' => function () {
                $world = new TestWorld();
                $corncob = $world->placeCharacter(new _05Corncob(), Game::LOCATION_CITY_DOCKS, 1);
                // WHY: FakeGame characterIsInDiscardOrLocker uses the per-card map, not Location.
                $world->game->discardOrLockerByCardId[$corncob->Id] = true;
                $deck = $world->game->getPlayerFactionDeckName(1);
                $card = $world->placeCard(new GenericCharacter('Deck'), $deck, 1);
                $world->game->topFactionCards = [['id' => $card->Id]];

                $event = new EventPhasePlanningEnd();
                $world->fireOn($corncob, $event);

                Assert::same($deck, $card->Location, 'not milled');
            },
        ];
    }
}
