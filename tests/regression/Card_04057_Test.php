<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04057;

class Card_04057_Test extends TestCase
{
    public function name(): string
    {
        return '_04057 Honorable';
    }

    public function tests(): array
    {
        return [
            'is a unique virtue risk' => function () {
                $card = new _04057();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(2, $card->Riposte, 'riposte');
                Assert::same(0, $card->Parry, 'parry');
                Assert::same(1, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Virtue'), 'Virtue');
                Assert::true($card->hasTrait('Challenge'), 'Challenge');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
