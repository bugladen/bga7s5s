<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02038;

class Card_02038_Test extends TestCase
{
    public function name(): string
    {
        return '_02038 Gold or Steel';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Risk with Pirate and Duelist maneuvers' => function () {
                $risk = new _02038();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(2, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::true($risk->DashedParry, 'dashed Parry');
                Assert::same(2, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Crime'), 'Crime');
                Assert::true($risk->hasTrait('Theft'), 'Theft');
                Assert::count(2, $risk->getManeuvers(), 'two');
                Assert::instanceOf(Maneuver_02038::class, $risk->getManeuvers()[0], 'pirate');
                Assert::instanceOf(Maneuver_02038::class, $risk->getManeuvers()[1], 'duelist');
                Assert::same('Maneuver_02038_Pirate', $risk->getManeuvers()[0]->Id, 'pirate id');
                Assert::same('Maneuver_02038_Duelist', $risk->getManeuvers()[1]->Id, 'duelist id');
            },

            'owns both maneuvers when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02038(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'pirate');
                Assert::same($risk->Id, $risk->getManeuvers()[1]->OwnerId, 'duelist');
                Assert::true(str_ends_with($risk->getManeuvers()[0]->Id, 'Maneuver_02038_Pirate'), 'pirate id');
                Assert::true(str_ends_with($risk->getManeuvers()[1]->Id, 'Maneuver_02038_Duelist'), 'duelist id');
            },
        ];
    }
}
