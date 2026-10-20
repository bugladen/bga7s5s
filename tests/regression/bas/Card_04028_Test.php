<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04028;

class Card_04028_Test extends TestCase
{
    public function name(): string
    {
        return '_04028 Depose';
    }

    public function tests(): array
    {
        return [
            'is a duty arrest justice risk' => function () {
                $card = new _04028();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(2, $card->Riposte, 'riposte');
                Assert::same(0, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Arrest'), 'Arrest');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
