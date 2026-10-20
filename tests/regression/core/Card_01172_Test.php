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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01172;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01172;

class Card_01172_Test extends TestCase
{
    public function name(): string
    {
        return '_01172 Pull';
    }

    public function tests(): array
    {
        return [
            'constructs Sorcery Sorte Risk with Action_01172' => function () {
                $risk = new _01172();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($risk->hasTrait('Sorte'), 'Sorte');
                Assert::instanceOf(Action_01172::class, $risk->getActions()[0], 'Action');
            },

            'owns Action_01172 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01172(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getActions(), 'one');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },

            'state constant registered' => function () {
                Assert::same(401172, States::HIGH_DRAMA_PLAYER_TURN_01172, 'state');
            },
        ];
    }
}
