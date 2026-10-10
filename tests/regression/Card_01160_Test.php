<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01160;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01160;

class Card_01160_Test extends TestCase
{
    public function name(): string
    {
        return '_01160 Bleed Out';
    }

    public function tests(): array
    {
        return [
            'constructs Neutral Villainous Risk that targets characters' => function () {
                $risk = new _01160();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::true($risk->hasTrait('Villainous'), 'Villainous');
                Assert::instanceOf(Action_01160::class, $risk->getActions()[0], 'Action_01160');
            },

            'costs 1 Wealth with Riposte 1, dashed Parry, Thrust 3' => function () {
                $risk = new _01160();
                Assert::same(1, $risk->WealthCost, 'wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::true($risk->DashedParry, 'dashed Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
            },

            'Action OwnerId is wired after setId' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01160(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
