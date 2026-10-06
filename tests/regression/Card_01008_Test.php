<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;

class Card_01008_Test extends TestCase
{
    public function name(): string
    {
        return '_01008 Cesca del Rosso';
    }

    public function tests(): array
    {
        return [
            'constructs Strega with Action and Reaction composites' => function () {
                $cesca = new _01008();
                Assert::instanceOf(IHasActions::class, $cesca, 'actions');
                Assert::instanceOf(IHasReactions::class, $cesca, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $cesca, 'techniques trait host');
                Assert::instanceOf(IHasManeuvers::class, $cesca, 'maneuvers trait host');
                Assert::same(5, $cesca->Resolve, 'Resolve');
                Assert::same(1, $cesca->Combat, 'Combat');
                Assert::same(1, $cesca->Finesse, 'Finesse');
                Assert::same(2, $cesca->Influence, 'Influence');
                Assert::true($cesca->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($cesca->hasTrait('Strega'), 'Strega');
                Assert::true($cesca->hasTrait('Red Hand'), 'Red Hand');
                Assert::instanceOf(Action_01008::class, $cesca->getActions()[0], 'Action_01008');
                Assert::instanceOf(Reaction_01008::class, $cesca->getReactions()[0], 'Reaction_01008');
                Assert::instanceOf(ISorcererAbility::class, $cesca->getActions()[0], 'action is sorcerer ability');
            },

            'state constants for reveal/sink flow' => function () {
                Assert::same(401008, States::HIGH_DRAMA_PLAYER_TURN_01008, '01008');
                Assert::same(4010082, States::HIGH_DRAMA_PLAYER_TURN_01008_2, '01008_2');
                Assert::same(4010083, States::HIGH_DRAMA_PLAYER_TURN_01008_3, '01008_3');
                Assert::same(4010084, States::HIGH_DRAMA_PLAYER_TURN_01008_4, '01008_4');
            },
        ];
    }
}
