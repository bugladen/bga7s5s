<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03059;

class Card_03059_Test extends TestCase
{
    public function name(): string
    {
        return '_03059 Insightful';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran flourish risk' => function () {
                $card = new _03059();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(1, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Virtue'), 'Virtue');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::instanceOf(Maneuver_03059::class, $card->getManeuvers()[0], 'maneuver');
            },
        ];
    }
}
