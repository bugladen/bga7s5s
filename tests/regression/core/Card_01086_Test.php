<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01086;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01086;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01086;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01086_Test extends TestCase
{
    public function name(): string
    {
        return '_01086 Status Matters';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk with Action_01086 and Maneuver_01086' => function () {
                $card = new _01086();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Demoralize'), 'Demoralize');
                Assert::true($card->hasTrait('Valroux'), 'Valroux');
                Assert::true($card->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::count(1, $card->getManeuvers(), 'one maneuver');
                Assert::instanceOf(Action_01086::class, $card->getActions()[0], 'Action_01086');
                Assert::instanceOf(Maneuver_01086::class, $card->getManeuvers()[0], 'Maneuver_01086');
            },
        ];
    }
}
