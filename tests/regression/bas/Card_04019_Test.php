<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04019;

class Card_04019_Test extends TestCase
{
    public function name(): string
    {
        return '_04019 No More Words!';
    }

    public function tests(): array
    {
        return [
            'is an eisenfaust challenge risk' => function () {
                $card = new _04019();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(2, $card->Parry, 'parry');
                Assert::true($card->hasTrait('Eisenfaust'), 'Eisenfaust');
                Assert::true($card->hasTrait('Zeal'), 'Zeal');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
