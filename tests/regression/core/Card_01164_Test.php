<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01164;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01164;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01164;

class Card_01164_Test extends TestCase
{
    public function name(): string
    {
        return '_01164 Hidden Corridors';
    }

    public function tests(): array
    {
        return [
            'constructs Flourish Stealth Risk with City Action and Maneuver' => function () {
                $risk = new _01164();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(1, $risk->WealthCost, 'cost');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::true($risk->DashedThrust, 'dashed Thrust');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Stealth'), 'Stealth');
                Assert::instanceOf(Action_01164::class, $risk->getActions()[0], 'Action_01164');
                Assert::instanceOf(Maneuver_01164::class, $risk->getManeuvers()[0], 'Maneuver_01164');
            },
        ];
    }
}
