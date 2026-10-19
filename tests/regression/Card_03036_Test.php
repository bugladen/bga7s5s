<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03036;

class Card_03036_Test extends TestCase
{
    public function name(): string
    {
        return '_03036 Valroux Exemplar';
    }

    public function tests(): array
    {
        return [
            'constructs a Valroux flourish risk' => function () {
                $risk = new _03036();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(1, $risk->WealthCost, 'wealth');
                Assert::same(2, $risk->Riposte, 'riposte');
                Assert::true($risk->DashedParry, 'dashed parry');
                Assert::true($risk->DashedThrust, 'dashed thrust');
                Assert::true($risk->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($risk->hasTrait('Valroux'), 'Valroux');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Demoralize'), 'Demoralize');
                Assert::instanceOf(Maneuver_03036::class, $risk->getManeuvers()[0], 'maneuver');
            },
        ];
    }
}
