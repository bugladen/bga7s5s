<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03070;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03070;

class Card_03070_Test extends TestCase
{
    public function name(): string
    {
        return '_03070 Comforting';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral virtue risk' => function () {
                $card = new _03070();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(4, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Virtue'), 'Virtue');
                Assert::instanceOf(Maneuver_03070::class, $card->getManeuvers()[0], 'maneuver');
            },
        ];
    }
}
