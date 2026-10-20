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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01174;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01174;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;

class Card_01174_Test extends TestCase
{
    public function name(): string
    {
        return '_01174 Shoddy Craftsmanship';
    }

    public function tests(): array
    {
        return [
            // WHY (journal 2026-10-08-01): card still implements IRiskThatTargetsCharacters
            // because no IRiskThatTargetsCards exists; Action targets attachments via cards.
            'constructs Sabotage Risk; Action targets cards' => function () {
                $risk = new _01174();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'card interface');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Sabotage'), 'Sabotage');
                Assert::instanceOf(Action_01174::class, $risk->getActions()[0], 'Action');
                Assert::instanceOf(IAbilityThatTargetsCards::class, $risk->getActions()[0], 'targets cards');
            },

            'owns Action_01174 when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01174(), Game::LOCATION_HAND, 1);
                Assert::count(1, $risk->getActions(), 'one');
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'owner');
            },

            'state constant registered' => function () {
                Assert::same(401174, States::HIGH_DRAMA_PLAYER_TURN_01174, 'state');
            },
        ];
    }
}
