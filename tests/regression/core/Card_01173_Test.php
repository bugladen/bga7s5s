<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01173;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01173;

class Card_01173_Test extends TestCase
{
    public function name(): string
    {
        return '_01173 Sea Legs';
    }

    public function tests(): array
    {
        return [
            'constructs Savvy Risk with Reaction_01173' => function () {
                $risk = new _01173();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::same(1, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Savvy'), 'Savvy');
                Assert::instanceOf(Reaction_01173::class, $risk->getReactions()[0], 'Reaction');
            },

            'owns Reaction_01173 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01173(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getReactions(), 'one');
                Assert::same($risk->Id, $risk->getReactions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
