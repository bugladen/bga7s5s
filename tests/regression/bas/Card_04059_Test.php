<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04059;

class Card_04059_Test extends TestCase
{
    public function name(): string
    {
        return '_04059 Silver Tongue';
    }

    public function tests(): array
    {
        return [
            'is a cunning savvy risk' => function () {
                $card = new _04059();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(2, $card->Parry, 'parry');
                Assert::true($card->DashedThrust, 'dashed thrust');
                Assert::true($card->hasTrait('Cunning'), 'Cunning');
                Assert::true($card->hasTrait('Savvy'), 'Savvy');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
