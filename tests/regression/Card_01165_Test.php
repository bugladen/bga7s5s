<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01165;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01165;

class Card_01165_Test extends TestCase
{
    public function name(): string
    {
        return '_01165 I Know that Trick!';
    }

    public function tests(): array
    {
        return [
            'constructs Flourish Prepared Risk with Maneuver_01165' => function () {
                $risk = new _01165();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'cost');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(1, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Prepared'), 'Prepared');
                Assert::instanceOf(Maneuver_01165::class, $risk->getManeuvers()[0], 'Maneuver_01165');
            },
        ];
    }
}
