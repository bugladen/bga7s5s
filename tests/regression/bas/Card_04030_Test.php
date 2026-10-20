<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04030;

class Card_04030_Test extends TestCase
{
    public function name(): string
    {
        return '_04030 Tip the Scales';
    }

    public function tests(): array
    {
        return [
            'is a flourish fortune risk' => function () {
                $card = new _04030();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::count(1, $card->getActions(), 'action');
                Assert::count(1, $card->getManeuvers(), 'maneuver');
            },
        ];
    }
}
