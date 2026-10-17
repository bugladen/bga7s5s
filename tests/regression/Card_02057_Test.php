<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02057;

class Card_02057_Test extends TestCase
{
    public function name(): string
    {
        return '_02057 Collateral Damage';
    }

    public function tests(): array
    {
        return [
            'constructs a Neutral Flourish Risk with dashed Riposte' => function () {
                $risk = new _02057();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(2, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Villainous'), 'Villainous');
                Assert::instanceOf(Maneuver_02057::class, $risk->getManeuvers()[0], 'maneuver');
            },

            'owns the maneuver when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02057(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'owner');
            },
        ];
    }
}
