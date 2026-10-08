<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01068;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01068;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;

class Card_01068_Test extends TestCase
{
    public function name(): string
    {
        return '_01068 Leontine Giroux';
    }

    public function tests(): array
    {
        return [
            'constructs Musketeer Sorcerer with printed stats' => function () {
                $leontine = new _01068();
                Assert::same(5, $leontine->Resolve, 'Resolve');
                Assert::same(2, $leontine->Combat, 'Combat');
                Assert::same(2, $leontine->Finesse, 'Finesse');
                Assert::same(2, $leontine->Influence, 'Influence');
                Assert::true($leontine->hasTrait('Duelist'), 'Duelist');
                Assert::true($leontine->hasTrait('Musketeer'), 'Musketeer');
                Assert::true($leontine->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($leontine->hasFaction('Montaigne'), 'Montaigne');
            },

            'has Sorcerer Action_01068' => function () {
                $leontine = new _01068();
                Assert::instanceOf(IHasActions::class, $leontine, 'actions');
                Assert::instanceOf(Action_01068::class, $leontine->getActions()[0], 'Action_01068');
                Assert::instanceOf(ISorcererAbility::class, $leontine->getActions()[0], 'Sorcerer ability');
            },
        ];
    }
}
