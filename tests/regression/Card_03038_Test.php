<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03038a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03038b;

class Card_03038_Test extends TestCase
{
    public function name(): string
    {
        return '_03038 Damya Kahina';
    }

    public function tests(): array
    {
        return [
            'constructs a Castille Numa with two city actions' => function () {
                $damya = new _03038();
                Assert::instanceOf(Character::class, $damya, 'character');
                Assert::instanceOf(IHasActions::class, $damya, 'actions');
                Assert::same(4, $damya->Resolve, 'resolve');
                Assert::same(1, $damya->Combat, 'combat');
                Assert::same(3, $damya->Finesse, 'finesse');
                Assert::same(2, $damya->Influence, 'influence');
                Assert::true($damya->hasFaction('Castille'), 'Castille');
                Assert::true($damya->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($damya->hasTrait('Pirate'), 'Pirate');
                Assert::true($damya->hasTrait('Fence'), 'Fence');
                Assert::true($damya->hasTrait('Numa'), 'Numa');
                Assert::instanceOf(Action_03038a::class, $damya->getActions()[0], 'draw');
                Assert::instanceOf(Action_03038b::class, $damya->getActions()[1], 'move');
            },
        ];
    }
}
