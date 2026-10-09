<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01106;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01106;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01106_Test extends TestCase
{
    public function name(): string
    {
        return '_01106 Improvising';
    }

    public function tests(): array
    {
        return [
            'is a Castille Ad Hoc Savvy Risk costing 0 Wealth' => function () {
                $risk = new _01106();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Ad Hoc'), 'Ad Hoc');
                Assert::true($risk->hasTrait('Savvy'), 'Savvy');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::same(2, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::same(1, $risk->Thrust, 'Thrust');
            },

            'owns Action_01106' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01106(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getActions(), 'one');
                Assert::instanceOf(Action_01106::class, $risk->getActions()[0], 'Action_01106');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
