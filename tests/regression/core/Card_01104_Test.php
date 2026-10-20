<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01104;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01104;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01104_Test extends TestCase
{
    public function name(): string
    {
        return '_01104 Amour';
    }

    public function tests(): array
    {
        return [
            'is a Castille Camaraderie Romance Risk costing 1 Wealth' => function () {
                $risk = new _01104();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::true($risk->hasTrait('Romance'), 'Romance');
                Assert::same(1, $risk->WealthCost, 'wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(0, $risk->Thrust, 'Thrust');
                Assert::true($risk->DashedThrust, 'dashed Thrust');
            },

            'owns Action_01104' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01104(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getActions(), 'one action');
                Assert::instanceOf(Action_01104::class, $risk->getActions()[0], 'Action_01104');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
