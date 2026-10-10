<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01133_Test extends TestCase
{
    public function name(): string
    {
        return '_01133 Matushka\'s Efficiency';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Sorcery Risk with Action, Reaction, Maneuver and WillEngage flag' => function () {
                $risk = new _01133();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(2, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::true($risk->DashedParry, 'dashed Parry');
                Assert::same(1, $risk->Thrust, 'Thrust');
                Assert::false($risk->WillEngage, 'WillEngage default');
                Assert::true($risk->hasFaction('Ussura'), 'Ussura');
                Assert::true($risk->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($risk->hasTrait('Dar Matushki'), 'Dar Matushki');
                Assert::instanceOf(Action_01133::class, $risk->getActions()[0], 'Action');
                Assert::instanceOf(Reaction_01133::class, $risk->getReactions()[0], 'Reaction');
                Assert::instanceOf(Maneuver_01133::class, $risk->getManeuvers()[0], 'Maneuver');
            },
        ];
    }
}
