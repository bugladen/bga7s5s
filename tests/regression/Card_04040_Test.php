<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04040;

class Card_04040_Test extends TestCase
{
    public function name(): string
    {
        return '_04040 Solvente Universal';
    }

    public function tests(): array
    {
        return [
            'is an alquimia acid risk' => function () {
                $card = new _04040();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(3, $card->Parry, 'parry');
                Assert::same(1, $card->Thrust, 'thrust');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::true($card->hasTrait('Acid'), 'Acid');
                Assert::true($card->hasTrait('Chymystry'), 'Chymystry');
                Assert::count(1, $card->getActions(), 'action');
                Assert::count(1, $card->getManeuvers(), 'maneuver');
            },
        ];
    }
}
