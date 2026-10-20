<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03020;

class Card_03020_Test extends TestCase
{
    public function name(): string
    {
        return '_03020 Commanding';
    }

    public function tests(): array
    {
        return [
            'constructs a free Eisen risk with a Leader Action and a Leader Reaction' => function () {
                $risk = new _03020();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::true($risk->DashedRiposte, 'dashed riposte');
                Assert::same(2, $risk->Parry, 'parry');
                Assert::same(2, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Eisen'), 'Eisen');
                Assert::true($risk->hasTrait('Virtue'), 'Virtue');
                Assert::instanceOf(Action_03020::class, $risk->getActions()[0], 'action');
                Assert::instanceOf(Reaction_03020::class, $risk->getReactions()[0], 'reaction');
            },

            'owns both composites when placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03020(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id, $risk->getActions()[0]->OwnerId, 'action');
                Assert::same($risk->Id, $risk->getReactions()[0]->OwnerId, 'reaction');
            },
        ];
    }
}
