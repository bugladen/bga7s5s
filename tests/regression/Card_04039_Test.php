<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04039;

class Card_04039_Test extends TestCase
{
    public function name(): string
    {
        return '_04039 Rapsodia';
    }

    public function tests(): array
    {
        return [
            'is a performance faith risk' => function () {
                $card = new _04039();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(1, $card->Parry, 'parry');
                Assert::true($card->DashedThrust, 'dashed thrust');
                Assert::true($card->hasTrait('Performance'), 'Performance');
                Assert::true($card->hasTrait('Faith'), 'Faith');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
