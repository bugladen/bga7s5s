<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;

class Card_01014_Test extends TestCase
{
    public function name(): string
    {
        return '_01014 Vittoria Anselmo';
    }

    public function tests(): array
    {
        return [
            'constructs with Reaction_01014 and stats' => function () {
                $vittoria = new _01014();
                Assert::instanceOf(IHasReactions::class, $vittoria, 'reactions');
                Assert::same(4, $vittoria->Resolve, 'Resolve');
                Assert::same(2, $vittoria->Combat, 'Combat');
                Assert::same(3, $vittoria->Finesse, 'Finesse');
                Assert::same(2, $vittoria->Influence, 'Influence');
                Assert::true($vittoria->hasTrait('Duelist'), 'Duelist');
                Assert::true($vittoria->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($vittoria->hasTrait('Vodacce'), 'Vodacce');
                Assert::instanceOf(Reaction_01014::class, $vittoria->getReactions()[0], 'Reaction_01014');
            },
        ];
    }
}
