<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02019;

class Card_02019_Test extends TestCase
{
    public function name(): string
    {
        return '_02019 Trial of Faith';
    }

    public function tests(): array
    {
        return [
            'constructs Eisen Risk with Reaction_02019' => function () {
                $risk = new _02019();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Eisen'), 'Eisen');
                Assert::true($risk->hasTrait('Faith'), 'Faith');
                Assert::true($risk->hasTrait('Zeal'), 'Zeal');
                Assert::instanceOf(Reaction_02019::class, $risk->getReactions()[0], 'Reaction');
            },

            'owns Reaction_02019 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02019(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getReactions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
