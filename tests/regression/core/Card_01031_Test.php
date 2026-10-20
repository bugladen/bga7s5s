<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01031_Test extends TestCase
{
    public function name(): string
    {
        return "_01031 Rough 'em Up";
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Maneuver_01031' => function () {
                $card = new _01031();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::true($card->DashedRiposte, 'DashedRiposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(3, $card->Thrust, 'Thrust');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Brawl'), 'Brawl');
                Assert::true($card->hasTrait('Gang'), 'Gang');
                Assert::instanceOf(Maneuver_01031::class, $card->getManeuvers()[0], 'Maneuver_01031');
            },
        ];
    }
}
