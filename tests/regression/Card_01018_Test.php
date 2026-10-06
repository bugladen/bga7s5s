<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class Card_01018_Test extends TestCase
{
    public function name(): string
    {
        return '_01018 Angelo';
    }

    public function tests(): array
    {
        return [
            'constructs Brute with Action_01018 and stats' => function () {
                $angelo = new _01018();
                Assert::instanceOf(Brute::class, $angelo, 'Brute');
                Assert::instanceOf(IHasActions::class, $angelo, 'actions');
                Assert::same(2, $angelo->Resolve, 'Resolve');
                Assert::same(1, $angelo->Combat, 'Combat');
                Assert::same(1, $angelo->Finesse, 'Finesse');
                Assert::true($angelo->DashedInfluence, 'DashedInfluence');
                Assert::true($angelo->DashedRiposte, 'DashedRiposte');
                Assert::same(2, $angelo->Parry, 'Parry');
                Assert::true($angelo->DashedThrust, 'DashedThrust');
                Assert::same(0, $angelo->WealthCost, 'WealthCost');
                Assert::true($angelo->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($angelo->hasTrait('Thug'), 'Thug');
                Assert::true($angelo->hasTrait('Brute'), 'Brute');
                Assert::instanceOf(Action_01018::class, $angelo->getActions()[0], 'Action_01018');
            },
        ];
    }
}
