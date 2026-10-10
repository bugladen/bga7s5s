<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01135;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01135;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01135;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01135_Test extends TestCase
{
    public function name(): string
    {
        return '_01135 Mireli\'s Revision';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Flourish Mireli Risk with Reaction and Maneuver' => function () {
                $risk = new _01135();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(1, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Ussura'), 'Ussura');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Mireli'), 'Mireli');
                Assert::instanceOf(Reaction_01135::class, $risk->getReactions()[0], 'Reaction');
                Assert::instanceOf(Maneuver_01135::class, $risk->getManeuvers()[0], 'Maneuver');
            },
        ];
    }
}
