<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04037;

class Card_04037_Test extends TestCase
{
    public function name(): string
    {
        return '_04037 Cats in Every Corner';
    }

    public function tests(): array
    {
        return [
            'is an ad hoc discovery risk' => function () {
                $card = new _04037();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::same(2, $card->Parry, 'parry');
                Assert::true($card->DashedThrust, 'dashed thrust');
                Assert::true($card->hasTrait('Discovery'), 'Discovery');
                Assert::true($card->hasTrait('Ad Hoc'), 'Ad Hoc');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
