<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02059;

class Card_02059_Test extends TestCase
{
    public function name(): string
    {
        return '_02059 Quick Reflexes';
    }

    public function tests(): array
    {
        return [
            'constructs a Neutral Ad Hoc Risk with a Reaction and a Maneuver' => function () {
                $risk = new _02059();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(2, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::true($risk->DashedParry, 'dashed Parry');
                Assert::same(0, $risk->Thrust, 'Thrust');
                Assert::true($risk->DashedThrust, 'dashed Thrust');
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Ad Hoc'), 'Ad Hoc');
                Assert::instanceOf(Reaction_02059::class, $risk->getReactions()[0], 'reaction');
                Assert::instanceOf(Maneuver_02059::class, $risk->getManeuvers()[0], 'maneuver');
            },

            'owns both composites when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02059(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getReactions()[0]->OwnerId, 'reaction');
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'maneuver');
            },
        ];
    }
}
