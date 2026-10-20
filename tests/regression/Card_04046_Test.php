<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04046;

class Card_04046_Test extends TestCase
{
    public function name(): string
    {
        return '_04046 Bravado';
    }

    public function tests(): array
    {
        return [
            'is a pride flourish risk' => function () {
                $card = new _04046();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Pride'), 'Pride');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::count(1, $card->getReactions(), 'reaction');
                Assert::count(1, $card->getManeuvers(), 'maneuver');
            },
        ];
    }
}
