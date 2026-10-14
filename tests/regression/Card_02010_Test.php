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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02010;

class Card_02010_Test extends TestCase
{
    public function name(): string
    {
        return '_02010 Twist of the Arcana';
    }

    public function tests(): array
    {
        return [
            'constructs Vodacce Sorcery Risk with Action_02010' => function () {
                $risk = new _02010();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(1, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($risk->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($risk->hasTrait('Sorte'), 'Sorte');
                Assert::instanceOf(Action_02010::class, $risk->getActions()[0], 'Action');
                Assert::instanceOf(RiskCityAction::class, $risk->getActions()[0], 'RiskCityAction');
            },

            'owns Action_02010 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02010(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getActions(), 'one');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },

            'state constants registered' => function () {
                Assert::same(402010, States::HIGH_DRAMA_PLAYER_TURN_02010, 'from');
                Assert::same(4020102, States::HIGH_DRAMA_PLAYER_TURN_02010_2, 'to');
                Assert::same(4020103, States::HIGH_DRAMA_PLAYER_TURN_02010_3, 'wounds');
            },
        ];
    }
}
