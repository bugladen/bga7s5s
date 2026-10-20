<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04056;

class Card_04056_Test extends TestCase
{
    public function name(): string
    {
        return '_04056 Greed';
    }

    public function tests(): array
    {
        return [
            'is a villainous envy risk' => function () {
                $card = new _04056();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(3, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Envy'), 'Envy');
                Assert::true($card->hasTrait('Covetous'), 'Covetous');
                Assert::true($card->hasTrait('Villainous'), 'Villainous');
                Assert::count(2, $card->getReactions(), 'reactions');
            },
        ];
    }
}
