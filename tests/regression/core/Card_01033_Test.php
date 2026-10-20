<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01033_Test extends TestCase
{
    public function name(): string
    {
        return "_01033 Veronica's Guile";
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action and Maneuver' => function () {
                $card = new _01033();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::true($card->DashedRiposte, 'DashedRiposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::true($card->DashedThrust, 'DashedThrust');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::true($card->hasTrait('Challenge'), 'Challenge');
                Assert::true($card->hasTrait('Ambrogia'), 'Ambrogia');
                Assert::instanceOf(Action_01033::class, $card->getActions()[0], 'Action_01033');
                Assert::instanceOf(Maneuver_01033::class, $card->getManeuvers()[0], 'Maneuver_01033');
            },
        ];
    }
}
