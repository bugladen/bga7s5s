<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03024;

class Card_03024_Test extends TestCase
{
    public function name(): string
    {
        return '_03024 Superstitious';
    }

    public function tests(): array
    {
        return [
            'constructs the Eisen maneuver risk' => function () {
                $risk = new _03024();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(1, $risk->Riposte, 'riposte');
                Assert::same(1, $risk->Parry, 'parry');
                Assert::same(2, $risk->Thrust, 'thrust');
                Assert::true($risk->hasTrait('Hubris'), 'Hubris');
            },
        ];
    }
}
