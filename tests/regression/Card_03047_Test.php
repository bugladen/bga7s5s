<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03047a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03047b;

class Card_03047_Test extends TestCase
{
    public function name(): string
    {
        return '_03047 Proper Drama';
    }

    public function tests(): array
    {
        return [
            'constructs a Castille savvy risk with two maneuvers' => function () {
                $risk = new _03047();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::same(2, $risk->Riposte, 'riposte');
                Assert::same(0, $risk->Parry, 'parry');
                Assert::same(0, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Savvy'), 'Savvy');
                Assert::true($risk->hasTrait('El Punal Occulto'), 'El Punal Occulto');
                Assert::instanceOf(Maneuver_03047a::class, $risk->getManeuvers()[0], 'scoundrel');
                Assert::instanceOf(Maneuver_03047b::class, $risk->getManeuvers()[1], 'duelist');
            },
        ];
    }
}
