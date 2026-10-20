<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01057_Test extends TestCase
{
    public function name(): string
    {
        return '_01057 Precision';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Maneuver_01057' => function () {
                $card = new _01057();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'DashedRiposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::same(4, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Ranged'), 'Ranged');
                Assert::count(1, $card->getManeuvers(), 'one maneuver');
                Assert::instanceOf(Maneuver_01057::class, $card->getManeuvers()[0], 'Maneuver_01057');
                Assert::instanceOf(IRangedAbility::class, $card->getManeuvers()[0], 'ranged ability');
            },
        ];
    }
}
