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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02020;

class Card_02020_Test extends TestCase
{
    public function name(): string
    {
        return '_02020 Yield';
    }

    public function tests(): array
    {
        return [
            'constructs Eisen Risk with Action_02020' => function () {
                $risk = new _02020();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(4, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Eisen'), 'Eisen');
                Assert::true($risk->hasTrait('Eisenfaust'), 'Eisenfaust');
                Assert::true($risk->hasTrait('Demoralize'), 'Demoralize');
                Assert::true($risk->hasTrait('Provocation'), 'Provocation');
                Assert::instanceOf(Action_02020::class, $risk->getActions()[0], 'Action');
            },

            'owns Action_02020 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02020(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },

            'state constants registered' => function () {
                Assert::same(402020, States::HIGH_DRAMA_PLAYER_TURN_02020, 'step 1');
                Assert::same(4020202, States::HIGH_DRAMA_PLAYER_TURN_02020_2, 'step 2');
                Assert::same(4020203, States::HIGH_DRAMA_PLAYER_TURN_02020_3, 'step 3');
            },
        ];
    }
}
