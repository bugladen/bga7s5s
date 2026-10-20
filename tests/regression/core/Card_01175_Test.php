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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01175;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01175;

class Card_01175_Test extends TestCase
{
    public function name(): string
    {
        return '_01175 Tending the Wounded';
    }

    public function tests(): array
    {
        return [
            // WHY (journal 2026-08-04-06): Action must extend RiskAction so discard/in-play
            // enumeration cannot surface it while the Risk is discarded.
            'constructs Faith Penance Risk with RiskAction_01175' => function () {
                $risk = new _01175();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(3, $risk->Parry, 'Parry');
                Assert::same(2, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Faith'), 'Faith');
                Assert::true($risk->hasTrait('Penance'), 'Penance');
                Assert::instanceOf(Action_01175::class, $risk->getActions()[0], 'Action');
                Assert::instanceOf(RiskAction::class, $risk->getActions()[0], 'RiskAction base');
            },

            'owns Action_01175 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01175(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getActions(), 'one');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },

            'state constant registered' => function () {
                Assert::same(401175, States::HIGH_DRAMA_PLAYER_TURN_01175, 'state');
            },
        ];
    }
}
