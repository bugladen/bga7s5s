<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01167;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01167;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01167_Test extends TestCase
{
    public function name(): string
    {
        return '_01167 Liberating Goods';
    }

    public function tests(): array
    {
        return [
            'constructs Crime Theft Risk with Action_01167' => function () {
                $risk = new _01167();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(0, $risk->WealthCost, 'WealthCost');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Crime'), 'Crime');
                Assert::true($risk->hasTrait('Theft'), 'Theft');
                // WHY: multi-faction shared Risk — no initializeFaction; Card defaults Neutral.
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::instanceOf(Action_01167::class, $risk->getActions()[0], 'Action_01167');
            },

            'ability id is stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01167(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id . '_Action_01167', $risk->getActions()[0]->Id, 'action id');
            },
        ];
    }
}
