<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03072;

class Card_03072_Test extends TestCase
{
    public function name(): string
    {
        return '_03072 Sabotage';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral flourish risk' => function () {
                $card = new _03072();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::true($card->DashedParry, 'dashed parry');
                Assert::same(5, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Demoralize'), 'Demoralize');
                Assert::true($card->hasTrait('Vandalize'), 'Vandalize');
                Assert::instanceOf(Action_03072::class, $card->getActions()[0], 'action');
                Assert::instanceOf(Maneuver_03072::class, $card->getManeuvers()[0], 'maneuver');
            },
        ];
    }
}
