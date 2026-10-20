<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04047;

class Card_04047_Test extends TestCase
{
    public function name(): string
    {
        return '_04047 Celerity';
    }

    public function tests(): array
    {
        return [
            'is an uncancelable challenge risk' => function () {
                $card = new _04047();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::same(2, $card->Riposte, 'riposte');
                Assert::true($card->DashedParry, 'dashed parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Challenge'), 'Challenge');
                Assert::true($card->effectsCannotBeCancelled(), 'cannot be cancelled');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
