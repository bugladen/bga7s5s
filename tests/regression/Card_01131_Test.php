<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01131_Test extends TestCase
{
    public function name(): string
    {
        return '_01131 Iron and Velvet';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Challenge Kulachniy Boi Risk with Action and Maneuver' => function () {
                $risk = new _01131();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(2, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Ussura'), 'Ussura');
                Assert::true($risk->hasTrait('Challenge'), 'Challenge');
                Assert::true($risk->hasTrait('Kulachniy Boi'), 'Kulachniy Boi');
                Assert::instanceOf(Action_01131::class, $risk->getActions()[0], 'Action_01131');
                Assert::instanceOf(Maneuver_01131::class, $risk->getManeuvers()[0], 'Maneuver_01131');
            },
        ];
    }
}
