<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04008;

class Card_04008_Test extends TestCase
{
    public function name(): string
    {
        return "_04008 Fate's Silence";
    }

    public function tests(): array
    {
        return [
            'is a unique sorte risk that targets a character' => function () {
                $card = new _04008();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::true($card->hasTrait('Sorte'), 'Sorte');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
