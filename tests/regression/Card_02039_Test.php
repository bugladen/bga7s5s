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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02039;

class Card_02039_Test extends TestCase
{
    public function name(): string
    {
        return '_02039 Raise the Stakes';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Aldana Risk with Reaction and Maneuver' => function () {
                $risk = new _02039();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(2, $risk->Thrust, 'Thrust');
                Assert::false($risk->DashedRiposte, 'riposte not dashed');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Aldana'), 'Aldana');
                Assert::true($risk->hasTrait('Duress'), 'Duress');
                Assert::instanceOf(Reaction_02039::class, $risk->getReactions()[0], 'reaction');
                Assert::instanceOf(Maneuver_02039::class, $risk->getManeuvers()[0], 'maneuver');
            },

            'owns reaction and maneuver when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02039(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getReactions()[0]->OwnerId, 'reaction');
                Assert::same($risk->Id, $risk->getManeuvers()[0]->OwnerId, 'maneuver');
            },
        ];
    }
}
