<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01166;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01166;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01166_Test extends TestCase
{
    public function name(): string
    {
        return "_01166 I'm Done With You";
    }

    public function tests(): array
    {
        return [
            'constructs Flourish Demoralize Risk with Maneuver_01166' => function () {
                $risk = new _01166();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'WealthCost');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(0, $risk->Thrust, 'Thrust');
                Assert::true($risk->DashedThrust, 'dashed Thrust');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Demoralize'), 'Demoralize');
                // WHY: multi-faction shared Risk — no initializeFaction; Card defaults Neutral.
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::instanceOf(Maneuver_01166::class, $risk->getManeuvers()[0], 'Maneuver_01166');
            },

            'ability id is stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01166(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id . '_Maneuver_01166', $risk->getManeuvers()[0]->Id, 'maneuver id');
            },
        ];
    }
}
