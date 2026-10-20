<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01052_Test extends TestCase
{
    public function name(): string
    {
        return '_01052 Fight Through the Pain';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01052 and Maneuver_01052' => function () {
                $card = new _01052();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'DashedRiposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(5, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Relentless'), 'Relentless');
                Assert::true($card->hasTrait('Eisenfaust'), 'Eisenfaust');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Action_01052::class, $card->getActions()[0], 'Action_01052');
                Assert::instanceOf(Maneuver_01052::class, $card->getManeuvers()[0], 'Maneuver_01052');
            },
        ];
    }
}