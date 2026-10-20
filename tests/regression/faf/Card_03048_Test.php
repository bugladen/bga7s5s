<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03048;

class Card_03048_Test extends TestCase
{
    public function name(): string
    {
        return '_03048 Wily';
    }

    public function tests(): array
    {
        return [
            'constructs a Castille flourish risk' => function () {
                $risk = new _03048();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(1, $risk->WealthCost, 'wealth');
                Assert::true($risk->DashedRiposte, 'dashed riposte');
                Assert::true($risk->DashedParry, 'dashed parry');
                Assert::same(1, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Virtue'), 'Virtue');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::instanceOf(Maneuver_03048::class, $risk->getManeuvers()[0], 'maneuver');
            },
        ];
    }
}
