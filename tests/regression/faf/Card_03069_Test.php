<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03069;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03069a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03069b;

class Card_03069_Test extends TestCase
{
    public function name(): string
    {
        return '_03069 Hop on Board';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral relentless risk with two maneuvers' => function () {
                $card = new _03069();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(1, $card->WealthCost, 'wealth');
                Assert::same(2, $card->Riposte, 'riposte');
                Assert::true($card->DashedParry, 'dashed parry');
                Assert::true($card->DashedThrust, 'dashed thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Relentless'), 'Relentless');
                Assert::true($card->hasTrait('Ad Hoc'), 'Ad Hoc');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::instanceOf(Maneuver_03069a::class, $card->getManeuvers()[0], 'swap');
                Assert::instanceOf(Maneuver_03069b::class, $card->getManeuvers()[1], 'gamble swap');
            },
        ];
    }
}
