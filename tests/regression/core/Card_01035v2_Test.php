<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01035v2;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01035v2;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01035v2;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class Card_01035v2_Test extends TestCase
{
    public function name(): string
    {
        return '_01035v2 Kaspar Dietrich';
    }

    public function tests(): array
    {
        return [
            'constructs Leader with Action_01035v2 and stats' => function () {
                $kaspar = new _01035v2();
                Assert::instanceOf(Leader::class, $kaspar, 'Leader');
                Assert::instanceOf(IHasActions::class, $kaspar, 'actions');
                Assert::instanceOf(IHasReactions::class, $kaspar, 'reactions');
                Assert::same(9, $kaspar->Resolve, 'Resolve');
                Assert::same(3, $kaspar->Combat, 'Combat');
                Assert::same(2, $kaspar->Finesse, 'Finesse');
                Assert::same(2, $kaspar->Influence, 'Influence');
                Assert::same(6, $kaspar->CrewCap, 'CrewCap');
                Assert::same(6, $kaspar->Panache, 'Panache');
                Assert::true($kaspar->hasTrait('Leader'), 'Leader trait');
                Assert::true($kaspar->hasTrait('Hero'), 'Hero');
                Assert::true($kaspar->hasTrait('General'), 'General');
                Assert::true($kaspar->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Action_01035v2::class, $kaspar->getActions()[0], 'Action_01035v2');
                Assert::instanceOf(Reaction_01035v2::class, $kaspar->getReactions()[0], 'Reaction_01035v2');
            },
        ];
    }
}
