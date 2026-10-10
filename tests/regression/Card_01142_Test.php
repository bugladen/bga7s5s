<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01142;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01142;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01142_Test extends TestCase
{
    public function name(): string
    {
        return '_01142 Sunder';
    }

    public function tests(): array
    {
        return [
            // WHY (journal 2026-10-05-06): Traits used to say "Flouish"; pin Flourish spelling.
            'constructs Ussura Flourish Demoralize Risk with Maneuver_01142' => function () {
                $risk = new _01142();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(1, $risk->WealthCost, 'cost');
                Assert::same(2, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::same(2, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Ussura'), 'Ussura');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish (not Flouish)');
                Assert::false($risk->hasTrait('Flouish'), 'typo gone');
                Assert::true($risk->hasTrait('Demoralize'), 'Demoralize');
                Assert::instanceOf(Maneuver_01142::class, $risk->getManeuvers()[0], 'Maneuver_01142');
            },
        ];
    }
}
