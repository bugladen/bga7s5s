<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04058;

class Card_04058_Test extends TestCase
{
    public function name(): string
    {
        return '_04058 Shield Rite';
    }

    public function tests(): array
    {
        return [
            'is a shamanism rite' => function () {
                $card = new _04058();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($card->hasTrait('Shamanism'), 'Shamanism');
                Assert::true($card->hasTrait('Rite'), 'Rite');
                Assert::count(1, $card->getReactions(), 'reaction');
                Assert::count(1, $card->getManeuvers(), 'maneuver');
            },
        ];
    }
}
