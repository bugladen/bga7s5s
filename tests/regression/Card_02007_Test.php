<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02007;

class Card_02007_Test extends TestCase
{
    public function name(): string
    {
        return '_02007 Arson';
    }

    public function tests(): array
    {
        return [
            'constructs Vodacce Crime Risk with Action_02007' => function () {
                $risk = new _02007();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($risk->hasTrait('Crime'), 'Crime');
                Assert::true($risk->hasTrait('Sabotage'), 'Sabotage');
                Assert::instanceOf(Action_02007::class, $risk->getActions()[0], 'Action');
                Assert::instanceOf(RiskCityAction::class, $risk->getActions()[0], 'RiskCityAction');
            },

            'owns Action_02007 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02007(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getActions(), 'one');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },

            'state constant registered' => function () {
                Assert::same(402007, States::HIGH_DRAMA_PLAYER_TURN_02007, 'chooser');
            },
        ];
    }
}
