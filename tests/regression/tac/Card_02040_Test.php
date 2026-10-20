<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02040;

class Card_02040_Test extends TestCase
{
    public function name(): string
    {
        return '_02040 Siesta';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Rest Risk with dashed Thrust and a City Action' => function () {
                $risk = new _02040();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(0, $risk->Thrust, 'Thrust');
                Assert::true($risk->DashedThrust, 'dashed Thrust');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Rest'), 'Rest');
                Assert::true($risk->hasTrait('Recuperation'), 'Recuperation');
                Assert::instanceOf(Action_02040::class, $risk->getActions()[0], 'action');
            },

            'owns the action when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02040(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
