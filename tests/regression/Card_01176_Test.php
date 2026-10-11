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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01176;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01176;

class Card_01176_Test extends TestCase
{
    public function name(): string
    {
        return '_01176 Triage';
    }

    public function tests(): array
    {
        return [
            'constructs Heroic Risk with RiskAction_01176' => function () {
                $risk = new _01176();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::same(4, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Heroic'), 'Heroic');
                Assert::instanceOf(Action_01176::class, $risk->getActions()[0], 'Action');
                Assert::instanceOf(RiskAction::class, $risk->getActions()[0], 'RiskAction base');
            },

            'owns Action_01176 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01176(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getActions(), 'one');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },

            // WHY: Triage resolves on EventActionTriggered after performer pick — no dedicated
            // HIGH_DRAMA_PLAYER_TURN_01176 state (unlike discard-to-heal siblings such as 01175).
            'has no dedicated HIGH_DRAMA state constant' => function () {
                Assert::false(
                    defined(States::class . '::HIGH_DRAMA_PLAYER_TURN_01176'),
                    'no HIGH_DRAMA_PLAYER_TURN_01176'
                );
            },
        ];
    }
}
