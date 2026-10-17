<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03012;

class Card_03012_Test extends TestCase
{
    public function name(): string
    {
        return '_03012 Subtle';
    }

    public function tests(): array
    {
        return [
            'constructs a dashed-Riposte Vodacce Sorcery risk' => function () {
                $risk = new _03012();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::true($risk->DashedRiposte, 'dashed riposte');
                Assert::same(3, $risk->Parry, 'parry');
                Assert::same(1, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($risk->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($risk->hasTrait('Sorte'), 'Sorte');
                Assert::instanceOf(Reaction_03012::class, $risk->getReactions()[0], 'reaction');
            },

            'owns the reaction when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03012(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getReactions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
