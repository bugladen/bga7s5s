<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;

class Card_01012_Test extends TestCase
{
    public function name(): string
    {
        return '_01012 Sibella Scarpa';
    }

    public function tests(): array
    {
        return [
            'constructs Strega with Action_01012 and stats' => function () {
                $sibella = new _01012();
                Assert::instanceOf(IHasActions::class, $sibella, 'has actions');
                Assert::same(4, $sibella->Resolve, 'Resolve');
                Assert::same(2, $sibella->Combat, 'Combat');
                Assert::same(2, $sibella->Finesse, 'Finesse');
                Assert::same(3, $sibella->Influence, 'Influence');
                Assert::true($sibella->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($sibella->hasTrait('Strega'), 'Strega');
                Assert::true($sibella->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($sibella->hasTrait('Vodacce'), 'Vodacce');
                Assert::instanceOf(Action_01012::class, $sibella->getActions()[0], 'Action_01012');
                Assert::instanceOf(ISorcererAbility::class, $sibella->getActions()[0], 'sorcerer ability');
            },

            'state constant registered' => function () {
                Assert::same(401012, States::HIGH_DRAMA_PLAYER_TURN_01012, 'state id');
            },
        ];
    }
}
