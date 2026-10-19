<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04020;

class Card_04020_Test extends TestCase
{
    public function name(): string
    {
        return '_04020 Vantage Point';
    }

    public function tests(): array
    {
        return [
            'is a ranged ambush risk' => function () {
                $card = new _04020();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(4, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Ambush'), 'Ambush');
                Assert::count(1, $card->getReactions(), 'reaction');
            },
        ];
    }
}
