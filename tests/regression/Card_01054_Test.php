<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01054_Test extends TestCase
{
    public function name(): string
    {
        return '_01054 Iron Reply';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Maneuver_01054' => function () {
                $card = new _01054();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::true($card->DashedParry, 'DashedParry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Eisenfaust'), 'Eisenfaust');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Maneuver_01054::class, $card->getManeuvers()[0], 'Maneuver_01054');
            },
        ];
    }
}