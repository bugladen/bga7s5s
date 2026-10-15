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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02029;

class Card_02029_Test extends TestCase
{
    public function name(): string
    {
        return '_02029 Diplomatic Impunity';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk with Action and Maneuver' => function () {
                $risk = new _02029();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(0, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($risk->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::instanceOf(Action_02029::class, $risk->getActions()[0], 'Action');
                Assert::instanceOf(Maneuver_02029::class, $risk->getManeuvers()[0], 'Maneuver');
            },

            'owns composites when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02029(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'action');
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'maneuver');
            },
        ];
    }
}
