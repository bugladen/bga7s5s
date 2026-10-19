<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;

class Card_04017_Test extends TestCase
{
    public function name(): string
    {
        return '_04017 Jägerarmbrust';
    }

    public function tests(): array
    {
        return [
            'is a unique ranged crossbow that reveals one more card for its bearer' => function () {
                $card = new _04017();
                Assert::instanceOf(FactionAttachment::class, $card, 'FactionAttachment');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::true($card->hasTrait('Ranged'), 'Ranged');
                Assert::true($card->hasTrait('Crossbow'), 'Crossbow');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::count(1, $card->getTechniques(), 'technique');

                $world = new TestWorld();
                $bow = $world->placeCard($card, Game::LOCATION_CITY_DOCKS, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                $bow->AttachedToId = $host->Id;
                $explanations = [];
                Assert::same(1, $bow->getNumberOfGambleCardsToReveal($world->theah, $host, $explanations), 'bearer');
                Assert::same(0, $bow->getNumberOfGambleCardsToReveal($world->theah, $other, $explanations), 'someone else');
                $bow->AttachedToId = 0;
                Assert::same(0, $bow->getNumberOfGambleCardsToReveal($world->theah, $host, $explanations), 'unequipped');
            },
        ];
    }
}
