<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02058;

class Card_02058_Test extends TestCase
{
    public function name(): string
    {
        return '_02058 Heroic Intervention';
    }

    public function tests(): array
    {
        return [
            'constructs a Neutral Duty Risk with a Reaction' => function () {
                $risk = new _02058();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(2, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::true($risk->hasTrait('Heroic'), 'Heroic');
                Assert::true($risk->hasTrait('Duty'), 'Duty');
                Assert::instanceOf(Reaction_02058::class, $risk->getReactions()[0], 'reaction');
            },

            'owns the reaction when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02058(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getReactions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
