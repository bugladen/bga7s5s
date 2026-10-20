<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd04;

class Card_04cd04_Test extends TestCase
{
    public function name(): string
    {
        return '_04cd04 Astrid de Martinez';
    }

    public function tests(): array
    {
        return [
            'is a negotiable mercenary diplomat' => function () {
                $card = new _04cd04();
                Assert::instanceOf(CityCharacter::class, $card, 'CityCharacter');
                Assert::same(4, $card->CityCardNumber, 'city number');
                Assert::true($card->Negotiable, 'negotiable');
                Assert::same(2, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($card->hasTrait('Castille'), 'Castille');
            },

            // WHY: En Garde is Engaged=false. The bonus is only this Influence hook,
            // and only when PRESSURING_PLAYER is someone else.
            'gains influence only while en garde against an opponent pressure' => function () {
                $world = new TestWorld();
                $astrid = $world->placeCharacter(new _04cd04(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same(2, $astrid->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'no pressuring player');

                $world->game->globals->set(Game::PRESSURING_PLAYER, 2);
                Assert::same(3, $astrid->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'opponent');

                $astrid->Engaged = true;
                Assert::same(2, $astrid->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'engaged');
                $astrid->Engaged = false;

                $world->game->globals->set(Game::PRESSURING_PLAYER, 1);
                Assert::same(2, $astrid->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'her controller');

                $world->game->globals->set(Game::PRESSURING_PLAYER, 0);
                Assert::same(2, $astrid->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'cleared');
            },
        ];
    }
}
