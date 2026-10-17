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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03023;

class Card_03023_Test extends TestCase
{
    public function name(): string
    {
        return '_03023 Second Wind';
    }

    public function tests(): array
    {
        return [
            'constructs a free Eisen risk with a City Action and a Gambling Maneuver' => function () {
                $risk = new _03023();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::same(1, $risk->Riposte, 'riposte');
                Assert::same(0, $risk->Parry, 'parry');
                Assert::same(2, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Eisen'), 'Eisen');
                Assert::true($risk->hasTrait('Relentless'), 'Relentless');
                Assert::instanceOf(Action_03023::class, $risk->getActions()[0], 'action');
                Assert::instanceOf(Maneuver_03023::class, $risk->getManeuvers()[0], 'maneuver');
            },

            'owns both composites when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03023(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'action');
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'maneuver');
            },
        ];
    }
}
