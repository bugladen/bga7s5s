<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03011;

class Card_03011_Test extends TestCase
{
    public function name(): string
    {
        return '_03011 Provoking the Pack';
    }

    public function tests(): array
    {
        return [
            'constructs a free Vodacce Gang risk with a City Action and a Gambling Maneuver' => function () {
                $risk = new _03011();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::same(1, $risk->Riposte, 'riposte');
                Assert::same(1, $risk->Parry, 'parry');
                Assert::same(1, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($risk->hasTrait('Gang'), 'Gang');
                Assert::instanceOf(Action_03011::class, $risk->getActions()[0], 'action');
                Assert::instanceOf(Maneuver_03011::class, $risk->getManeuvers()[0], 'maneuver');
            },

            'owns the action and the maneuver when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03011(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'action owner');
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'maneuver owner');
            },
        ];
    }
}
