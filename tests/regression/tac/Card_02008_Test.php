<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02008;

class Card_02008_Test extends TestCase
{
    public function name(): string
    {
        return "_02008 Fate's Kiss";
    }

    public function tests(): array
    {
        return [
            'constructs Vodacce Sorcery Risk with Action_02008' => function () {
                $risk = new _02008();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($risk->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($risk->hasTrait('Sorte'), 'Sorte');
                Assert::instanceOf(Action_02008::class, $risk->getActions()[0], 'Action');
            },

            'owns Action_02008 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02008(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getActions(), 'one');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },

            'state constants registered' => function () {
                Assert::same(402008, States::HIGH_DRAMA_PLAYER_TURN_02008, 'risk chooser');
                Assert::same(4020082, States::HIGH_DRAMA_PLAYER_TURN_02008_2, 'character chooser');
            },
        ];
    }
}
