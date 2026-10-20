<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04029;

class Card_04029_Test extends TestCase
{
    public function name(): string
    {
        return '_04029 A Fine Addition...';
    }

    public function tests(): array
    {
        return [
            'is a villainous extortion risk' => function () {
                $card = new _04029();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Villainous'), 'Villainous');
                Assert::true($card->hasTrait('Fortune'), 'Fortune');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
