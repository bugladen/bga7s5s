<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04009;

class Card_04009_Test extends TestCase
{
    public function name(): string
    {
        return '_04009 Rattle the Rigging';
    }

    public function tests(): array
    {
        return [
            'is a provocation risk' => function () {
                $card = new _04009();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Provocation'), 'Provocation');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
