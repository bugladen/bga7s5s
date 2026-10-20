<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01171;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01171;

class Card_01171_Test extends TestCase
{
    public function name(): string
    {
        return '_01171 Paid Off';
    }

    public function tests(): array
    {
        return [
            'constructs Cunning Villainous Risk with Action_01171' => function () {
                $risk = new _01171();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(2, $risk->WealthCost, 'Wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::true($risk->DashedParry, 'dashed Parry');
                Assert::same(2, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Cunning'), 'Cunning');
                Assert::true($risk->hasTrait('Villainous'), 'Villainous');
                Assert::instanceOf(Action_01171::class, $risk->getActions()[0], 'Action');
            },

            'owns Action_01171 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01171(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getActions(), 'one');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },

            'state constant registered' => function () {
                Assert::same(401171, States::HIGH_DRAMA_PLAYER_TURN_01171, 'state');
            },
        ];
    }
}
