<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02050;

class Card_02050_Test extends TestCase
{
    public function name(): string
    {
        return '_02050 Ussuran Intrigue';
    }

    public function tests(): array
    {
        return [
            // WHY: The printed trait is spelled Beauracracy. hasTrait checks that string.
            'constructs Ussura Risk with a City Reaction' => function () {
                $risk = new _02050();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::false($risk->DashedRiposte, 'solid Riposte');
                Assert::true($risk->hasFaction('Ussura'), 'Ussura');
                Assert::true($risk->hasTrait('Cunning'), 'Cunning');
                Assert::true($risk->hasTrait('Beauracracy'), 'printed spelling');
                Assert::instanceOf(Reaction_02050::class, $risk->getReactions()[0], 'reaction');
            },

            'owns the reaction when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02050(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getReactions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
