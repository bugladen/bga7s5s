<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01059_Test extends TestCase
{
    public function name(): string
    {
        return '_01059 Regroup';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01059 and Maneuver_01059' => function () {
                $card = new _01059();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'DashedRiposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(0, $card->Thrust, 'Thrust');
                Assert::true($card->DashedThrust, 'DashedThrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Prepared'), 'Prepared');
                Assert::instanceOf(Action_01059::class, $card->getActions()[0], 'Action_01059');
                Assert::instanceOf(Maneuver_01059::class, $card->getManeuvers()[0], 'Maneuver_01059');
            },
        ];
    }
}
