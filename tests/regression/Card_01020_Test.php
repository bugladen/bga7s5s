<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class Card_01020_Test extends TestCase
{
    public function name(): string
    {
        return '_01020 Dante';
    }

    public function tests(): array
    {
        return [
            'constructs Brute with Action_01020 and stats' => function () {
                $dante = new _01020();
                Assert::instanceOf(Brute::class, $dante, 'Brute');
                Assert::instanceOf(IHasActions::class, $dante, 'actions');
                Assert::same(2, $dante->Resolve, 'Resolve');
                Assert::same(2, $dante->Combat, 'Combat');
                Assert::same(2, $dante->Finesse, 'Finesse');
                Assert::true($dante->DashedInfluence, 'DashedInfluence');
                Assert::same(4, $dante->Riposte, 'Riposte');
                Assert::true($dante->DashedParry, 'DashedParry');
                Assert::true($dante->DashedThrust, 'DashedThrust');
                Assert::same(2, $dante->WealthCost, 'WealthCost');
                Assert::true($dante->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($dante->hasTrait('Thug'), 'Thug');
                Assert::true($dante->hasTrait('Brute'), 'Brute');
                Assert::instanceOf(Action_01020::class, $dante->getActions()[0], 'Action_01020');
            },
        ];
    }
}
