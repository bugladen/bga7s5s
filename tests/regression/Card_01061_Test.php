<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01061_Test extends TestCase
{
    public function name(): string
    {
        return '_01061 Well-Equipped';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01061 and Maneuver_01061' => function () {
                $card = new _01061();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::true($card->DashedParry, 'DashedParry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Prepared'), 'Prepared');
                Assert::true($card->hasTrait('Drexel'), 'Drexel');
                Assert::instanceOf(Action_01061::class, $card->getActions()[0], 'Action_01061');
                Assert::instanceOf(Maneuver_01061::class, $card->getManeuvers()[0], 'Maneuver_01061');
            },
        ];
    }
}
