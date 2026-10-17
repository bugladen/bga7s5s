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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03022;

class Card_03022_Test extends TestCase
{
    public function name(): string
    {
        return '_03022 Overzealous';
    }

    public function tests(): array
    {
        return [
            'constructs an Eisen Faith risk with the Final Strike maneuver' => function () {
                $risk = new _03022();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::false($risk instanceof IHasActions, 'no action');
                Assert::same(1, $risk->WealthCost, 'wealth');
                Assert::same(0, $risk->Parry, 'parry');
                Assert::same(4, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Eisen'), 'Eisen');
                Assert::true($risk->hasTrait('Zeal'), 'Zeal');
            },

            'owns the maneuver when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03022(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'owner');
            },
        ];
    }
}
