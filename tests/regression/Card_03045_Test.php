<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03045;

class Card_03045_Test extends TestCase
{
    public function name(): string
    {
        return '_03045 Curious';
    }

    public function tests(): array
    {
        return [
            'constructs a Castille flourish risk' => function () {
                $risk = new _03045();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::same(1, $risk->Riposte, 'riposte');
                Assert::true($risk->DashedParry, 'dashed parry');
                Assert::same(1, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Hubris'), 'Hubris');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::instanceOf(Action_03045::class, $risk->getActions()[0], 'action');
                Assert::instanceOf(Maneuver_03045::class, $risk->getManeuvers()[0], 'maneuver');
            },
        ];
    }
}
