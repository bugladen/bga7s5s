<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04060;

class Card_04060_Test extends TestCase
{
    public function name(): string
    {
        return '_04060 Worldly';
    }

    public function tests(): array
    {
        return [
            'is a discovery flourish risk' => function () {
                $card = new _04060();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::same(2, $card->Riposte, 'riposte');
                Assert::true($card->DashedParry, 'dashed parry');
                Assert::same(0, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Discovery'), 'Discovery');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::count(1, $card->getActions(), 'action');
                Assert::count(1, $card->getManeuvers(), 'maneuver');
            },
        ];
    }
}
