<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02030a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02030b;

class Card_02030_Test extends TestCase
{
    public function name(): string
    {
        return '_02030 Fraternité!';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk with both Reactions' => function () {
                $risk = new _02030();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(3, $risk->Parry, 'Parry');
                Assert::same(2, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($risk->hasTrait('Duty'), 'Duty');
                Assert::true($risk->hasTrait('Ad Hoc'), 'Ad Hoc');
                Assert::true($risk->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::count(2, $risk->getReactions(), 'two');
                Assert::instanceOf(Reaction_02030a::class, $risk->getReactions()[0], 'a');
                Assert::instanceOf(Reaction_02030b::class, $risk->getReactions()[1], 'b');
            },

            'owns reactions when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02030(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getReactions()[0]->OwnerId, 'a');
                Assert::same($risk->Id, $risk->getReactions()[1]->OwnerId, 'b');
            },
        ];
    }
}
