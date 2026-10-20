<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02041;

class Card_02041_Test extends TestCase
{
    public function name(): string
    {
        return '_02041 You Cheated!';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Flourish Risk with a Scoundrel Maneuver' => function () {
                $risk = new _02041();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(2, $risk->Thrust, 'Thrust');
                Assert::false($risk->DashedThrust, 'solid Thrust');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Cheating'), 'Cheating');
                Assert::true($risk->hasTrait('Savvy'), 'Savvy');
                Assert::instanceOf(Maneuver_02041::class, $risk->getManeuvers()[0], 'maneuver');
            },

            'owns the maneuver when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02041(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'owner');
            },
        ];
    }
}
