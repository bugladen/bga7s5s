<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02018;

class Card_02018_Test extends TestCase
{
    public function name(): string
    {
        return '_02018 Divine Discipline';
    }

    public function tests(): array
    {
        return [
            'constructs Eisen Risk with Action and Maneuver' => function () {
                $risk = new _02018();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Eisen'), 'Eisen');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Faith'), 'Faith');
                Assert::true($risk->hasTrait('Penance'), 'Penance');
                Assert::instanceOf(Action_02018::class, $risk->getActions()[0], 'Action');
                Assert::instanceOf(Maneuver_02018::class, $risk->getManeuvers()[0], 'Maneuver');
            },

            'owns composites when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02018(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'action');
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'maneuver');
            },
        ];
    }
}
