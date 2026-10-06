<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class Card_01019_Test extends TestCase
{
    public function name(): string
    {
        return '_01019 Buratino';
    }

    public function tests(): array
    {
        return [
            'constructs Brute with Action_01019 and stats' => function () {
                $buratino = new _01019();
                Assert::instanceOf(Brute::class, $buratino, 'Brute');
                Assert::instanceOf(IHasActions::class, $buratino, 'actions');
                Assert::same(2, $buratino->Resolve, 'Resolve');
                Assert::same(3, $buratino->Combat, 'Combat');
                Assert::same(1, $buratino->Finesse, 'Finesse');
                Assert::true($buratino->DashedInfluence, 'DashedInfluence');
                Assert::same(1, $buratino->Riposte, 'Riposte');
                Assert::true($buratino->DashedParry, 'DashedParry');
                Assert::same(2, $buratino->Thrust, 'Thrust');
                Assert::same(1, $buratino->WealthCost, 'WealthCost');
                Assert::true($buratino->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($buratino->hasTrait('Thug'), 'Thug');
                Assert::true($buratino->hasTrait('Brute'), 'Brute');
                Assert::instanceOf(Action_01019::class, $buratino->getActions()[0], 'Action_01019');
            },
        ];
    }
}
