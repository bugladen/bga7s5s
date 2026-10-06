<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class Card_01017_Test extends TestCase
{
    public function name(): string
    {
        return '_01017 Alcee';
    }

    public function tests(): array
    {
        return [
            'constructs Brute with Action_01017 and stats' => function () {
                $alcee = new _01017();
                Assert::instanceOf(Brute::class, $alcee, 'Brute');
                Assert::instanceOf(IHasActions::class, $alcee, 'actions');
                Assert::same(2, $alcee->Resolve, 'Resolve');
                Assert::same(1, $alcee->Combat, 'Combat');
                Assert::same(2, $alcee->Finesse, 'Finesse');
                Assert::same(1, $alcee->Influence, 'Influence');
                Assert::same(2, $alcee->Riposte, 'Riposte');
                Assert::same(1, $alcee->Thrust, 'Thrust');
                Assert::true($alcee->DashedParry, 'DashedParry');
                Assert::same(2, $alcee->WealthCost, 'WealthCost');
                Assert::true($alcee->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($alcee->hasTrait('Thug'), 'Thug');
                Assert::true($alcee->hasTrait('Brute'), 'Brute');
                Assert::true($alcee->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Action_01017::class, $alcee->getActions()[0], 'Action_01017');
            },
        ];
    }
}
