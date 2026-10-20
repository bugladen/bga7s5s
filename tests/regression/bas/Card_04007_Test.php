<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04007;

class Card_04007_Test extends TestCase
{
    public function name(): string
    {
        return "_04007 Bernoulli's Approach";
    }

    public function tests(): array
    {
        return [
            'is a flourish risk with two duelist maneuvers' => function () {
                $card = new _04007();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(3, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Bernoulli'), 'Bernoulli');
                Assert::count(2, $card->getManeuvers(), 'maneuvers');
            },
        ];
    }
}
