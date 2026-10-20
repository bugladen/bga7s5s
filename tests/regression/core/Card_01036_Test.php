<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01036_Test extends TestCase
{
    public function name(): string
    {
        return '_01036 Daniella Dietrich';
    }

    public function tests(): array
    {
        return [
            'constructs Character with Action_01036 and Technique_01036' => function () {
                $daniella = new _01036();
                Assert::instanceOf(IHasActions::class, $daniella, 'actions');
                Assert::instanceOf(IHasTechniques::class, $daniella, 'techniques');
                Assert::same(4, $daniella->Resolve, 'Resolve');
                Assert::same(2, $daniella->Combat, 'Combat');
                Assert::same(3, $daniella->Finesse, 'Finesse');
                Assert::same(1, $daniella->Influence, 'Influence');
                Assert::true($daniella->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($daniella->hasTrait('Strega'), 'Strega');
                Assert::true($daniella->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Action_01036::class, $daniella->getActions()[0], 'Action_01036');
                Assert::instanceOf(Technique_01036::class, $daniella->getTechniques()[0], 'Technique_01036');
            },
        ];
    }
}
