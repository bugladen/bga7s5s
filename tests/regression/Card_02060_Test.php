<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02060a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02060b;

class Card_02060_Test extends TestCase
{
    public function name(): string
    {
        return '_02060 Relentless';
    }

    public function tests(): array
    {
        return [
            'constructs a Neutral Hubris Risk with two Reactions' => function () {
                $risk = new _02060();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::true($risk->DashedParry, 'dashed Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::false($risk->DashedThrust, 'solid Thrust');
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::true($risk->hasTrait('Hubris'), 'Hubris');
                Assert::instanceOf(Reaction_02060a::class, $risk->getReactions()[0], 'refused');
                Assert::instanceOf(Reaction_02060b::class, $risk->getReactions()[1], 'duel end');
            },

            'owns both reactions when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02060(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getReactions()[0]->OwnerId, 'a');
                Assert::same($risk->Id, $risk->getReactions()[1]->OwnerId, 'b');
            },
        ];
    }
}
