<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01123;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01123;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01123;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01123_Test extends TestCase
{
    public function name(): string
    {
        return '_01123 Valeri Mikhailov';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Duelist with Action and Technique' => function () {
                $valeri = new _01123();
                Assert::instanceOf(Character::class, $valeri, 'Character');
                Assert::instanceOf(IHasActions::class, $valeri, 'actions');
                Assert::instanceOf(IHasTechniques::class, $valeri, 'techniques');
                Assert::same(4, $valeri->Resolve, 'Resolve');
                Assert::same(2, $valeri->Combat, 'Combat');
                Assert::same(3, $valeri->Finesse, 'Finesse');
                Assert::same(1, $valeri->Influence, 'Influence');
                Assert::true($valeri->hasFaction('Ussura'), 'Ussura');
                Assert::true($valeri->hasTrait('Duelist'), 'Duelist');
                Assert::instanceOf(Action_01123::class, $valeri->getActions()[0], 'Action_01123');
                Assert::instanceOf(Technique_01123::class, $valeri->getTechniques()[0], 'Technique_01123');
            },
        ];
    }
}
