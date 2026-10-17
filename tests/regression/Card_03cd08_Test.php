<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd08;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;

class Card_03cd08_Test extends TestCase
{
    public function name(): string
    {
        return '_03cd08 Inauguration Day';
    }

    public function tests(): array
    {
        return [
            'constructs a Celebration Revelry city event' => function () {
                $card = new _03cd08();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::same(8, $card->CityCardNumber, 'city number');
                Assert::true($card->hasTrait('Celebration'), 'Celebration');
                Assert::true($card->hasTrait('Revelry'), 'Revelry');
            },

            // WHY: Sets the Claude pressure flag (OR, so other bits survive) and records
            // this card as claudeId. Only pressures at this city location.
            'pressure here counts only the performer and en garde characters' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd08(), Game::LOCATION_CITY_FORUM, 0);
                $world->game->globals->set(Game::PRESSURE_TYPE, 4);

                $pressure = new EventPressureOccuring();
                $pressure->location = Game::LOCATION_CITY_FORUM;
                $pressure->theah = $world->theah;
                $card->handleEvent($pressure);

                Assert::true(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE),
                    'claude pressure'
                );
                Assert::same(5, $world->game->globals->get(Game::PRESSURE_TYPE), 'previous bits kept');
                Assert::same($card->Id, $world->game->globals->get(Game::CLAUD_ID), 'this card');
            },

            'pressure at another location is unchanged' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd08(), Game::LOCATION_CITY_FORUM, 0);

                $pressure = new EventPressureOccuring();
                $pressure->location = Game::LOCATION_CITY_DOCKS;
                $pressure->theah = $world->theah;
                $card->handleEvent($pressure);

                Assert::false(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE),
                    'still normal'
                );
                Assert::same(null, $world->game->globals->get(Game::CLAUD_ID), 'no id');
            },

            'the card must be in the city' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd08(), Game::LOCATION_CITY_DISCARD, 0);
                $card->Location = Game::LOCATION_CITY_DISCARD;

                $pressure = new EventPressureOccuring();
                $pressure->location = Game::LOCATION_CITY_DISCARD;
                $pressure->theah = $world->theah;
                $card->handleEvent($pressure);

                Assert::false(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE),
                    'discarded card is silent'
                );
            },
        ];
    }
}
