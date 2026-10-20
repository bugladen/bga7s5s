<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04048;

class Card_04048_Test extends TestCase
{
    public function name(): string
    {
        return '_04048 Iaijutsu Strike';
    }

    public function tests(): array
    {
        return [
            'is a kenjutsu flourish risk' => function () {
                $card = new _04048();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(0, $card->Riposte, 'riposte');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Naito-ryu'), 'Naito-ryu');
                Assert::true($card->hasTrait('Kenjutsu'), 'Kenjutsu');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::count(2, $card->getManeuvers(), 'maneuvers');
            },
        ];
    }
}
