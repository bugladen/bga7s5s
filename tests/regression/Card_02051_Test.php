<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02051;

class Card_02051_Test extends TestCase
{
    public function name(): string
    {
        return '_02051 Voice of the Gods';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussura Sorcery Risk with a City Action' => function () {
                $risk = new _02051();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::same(4, $risk->Thrust, 'Thrust');
                Assert::false($risk->DashedThrust, 'solid Thrust');
                Assert::true($risk->hasFaction('Ussura'), 'Ussura');
                Assert::true($risk->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($risk->hasTrait('Galdr'), 'Galdr');
                Assert::true($risk->hasTrait('Storte Merke'), 'Storte Merke');
                Assert::instanceOf(Action_02051::class, $risk->getActions()[0], 'action');
            },

            'owns the action when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02051(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
