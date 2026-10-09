<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01103;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01103a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01103b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01103;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01103_Test extends TestCase
{
    public function name(): string
    {
        return '_01103 Adaptable';
    }

    public function tests(): array
    {
        return [
            'is a Castille Unique Flourish Virtue Risk costing 1 Wealth' => function () {
                $risk = new _01103();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Virtue'), 'Virtue');
                Assert::true($risk->hasTrait('Unique'), 'Unique');
                Assert::same(1, $risk->WealthCost, 'wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::same(0, $risk->Thrust, 'Thrust');
            },

            'owns Action_01103a, Action_01103b, and Maneuver_01103' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01103(), Game::LOCATION_HAND, 1);
                Assert::count(2, $risk->getActions(), 'two actions');
                Assert::instanceOf(Action_01103a::class, $risk->getActions()[0], 'a');
                Assert::instanceOf(Action_01103b::class, $risk->getActions()[1], 'b');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'a owner');
                Assert::same($risk->Id, $risk->getActions()[1]->OwnerId, 'b owner');
                Assert::count(1, $risk->getManeuvers(), 'one maneuver');
                Assert::instanceOf(Maneuver_01103::class, $risk->getManeuvers()[0], 'maneuver');
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'maneuver owner');
            },
        ];
    }
}
