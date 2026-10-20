<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02049;

class Card_02049_Test extends TestCase
{
    public function name(): string
    {
        return '_02049 Justice Served Cold';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Challenge Risk that targets characters' => function () {
                $risk = new _02049();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(1, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Ussura'), 'Ussura');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Heroic'), 'Heroic');
                Assert::true($risk->hasTrait('Challenge'), 'Challenge');
                Assert::instanceOf(Action_02049::class, $risk->getActions()[0], 'action');
                Assert::instanceOf(Maneuver_02049::class, $risk->getManeuvers()[0], 'maneuver');
            },

            'owns the action and maneuver when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02049(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'action');
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'maneuver');
            },
        ];
    }
}
