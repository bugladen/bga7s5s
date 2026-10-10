<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01168;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01168;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01168_Test extends TestCase
{
    public function name(): string
    {
        return '_01168 A New Strategy';
    }

    public function tests(): array
    {
        return [
            'constructs Logistics Savvy Risk with Action_01168' => function () {
                $risk = new _01168();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(0, $risk->WealthCost, 'WealthCost');
                Assert::same(2, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::true($risk->DashedParry, 'dashed Parry');
                Assert::same(1, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Logistics'), 'Logistics');
                Assert::true($risk->hasTrait('Savvy'), 'Savvy');
                // WHY: multi-faction shared Risk — no initializeFaction; Card defaults Neutral.
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::instanceOf(Action_01168::class, $risk->getActions()[0], 'Action_01168');
            },

            'ability id is stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01168(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id . '_Action_01168', $risk->getActions()[0]->Id, 'action id');
            },
        ];
    }
}
