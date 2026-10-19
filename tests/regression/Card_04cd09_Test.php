<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd09;

class Card_04cd09_Test extends TestCase
{
    public function name(): string
    {
        return '_04cd09 Knives Out';
    }

    public function tests(): array
    {
        return [
            'is an ambush cunning city event' => function () {
                $card = new _04cd09();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::same(9, $card->CityCardNumber, 'city number');
                Assert::true($card->hasTrait('Ambush'), 'Ambush');
                Assert::true($card->hasTrait('Cunning'), 'Cunning');
            },

            // WHY: Any challenge whose defender is at this city location. No challenge-type gate.
            'characters at this location cannot refuse challenges' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd09(), Game::LOCATION_CITY_DOCKS, 0);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
                Assert::true(_04cd09::challengeRefusalBlocked($world->theah, $defender), 'here');

                $defender->Location = Game::LOCATION_CITY_FORUM;
                Assert::false(_04cd09::challengeRefusalBlocked($world->theah, $defender), 'elsewhere');

                $defender->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false(_04cd09::challengeRefusalBlocked($world->theah, $defender), 'home');

                $defender->Location = Game::LOCATION_CITY_DOCKS;
                $card->Location = Game::LOCATION_CITY_DISCARD;
                Assert::false(_04cd09::challengeRefusalBlocked($world->theah, $defender), 'card left');
            },
        ];
    }
}
