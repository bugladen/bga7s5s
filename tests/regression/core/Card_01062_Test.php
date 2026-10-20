<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class Card_01062_Test extends TestCase
{
    public function name(): string
    {
        return "_01062 Odette Dubois D'Arrent";
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Leader with printed stats' => function () {
                $odette = new _01062();
                Assert::instanceOf(Leader::class, $odette, 'Leader');
                Assert::same(5, $odette->Resolve, 'Resolve');
                Assert::same(1, $odette->Combat, 'Combat');
                Assert::same(4, $odette->Finesse, 'Finesse');
                Assert::same(3, $odette->Influence, 'Influence');
                Assert::same(6, $odette->CrewCap, 'CrewCap');
                Assert::same(7, $odette->Panache, 'Panache');
                Assert::true($odette->hasTrait('Leader'), 'Leader trait');
                Assert::true($odette->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($odette->hasFaction('Montaigne'), 'Montaigne');
            },

            'has Action_01062 and Reaction_01062' => function () {
                $odette = new _01062();
                Assert::instanceOf(IHasActions::class, $odette, 'actions');
                Assert::instanceOf(IHasReactions::class, $odette, 'reactions');
                Assert::instanceOf(Action_01062::class, $odette->getActions()[0], 'Action_01062');
                Assert::instanceOf(Reaction_01062::class, $odette->getReactions()[0], 'Reaction_01062');
            },

            // WHY: "Musketeer may intervene without engaging" lives in FrameworkActionsTrait,
            // not on the card. This note test only locks that the printed rule stays on the
            // card text so a text edit does not silently drop the intent.
            'printed text documents Musketeer intervene without engaging' => function () {
                $odette = new _01062();
                Assert::contains('en garde Musketeer', $odette->Text, 'Musketeer rule');
                Assert::contains('intervene without engaging', $odette->Text, 'no engage');
            },
        ];
    }
}
