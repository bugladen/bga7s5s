<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04021;

class Card_04021_Test extends TestCase
{
    public function name(): string
    {
        return '_04021 Aimée Barrere';
    }

    public function tests(): array
    {
        return [
            'is a musketeer with a reaction and two techniques' => function () {
                $card = new _04021();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(4, $card->Resolve, 'resolve');
                Assert::same(2, $card->Combat, 'combat');
                Assert::same(3, $card->Finesse, 'finesse');
                Assert::same(2, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Musketeer'), 'Musketeer');
                Assert::true($card->hasTrait('Duelist'), 'Duelist');
                Assert::count(1, $card->getReactions(), 'reaction');
                Assert::count(2, $card->getTechniques(), 'techniques');
            },
        ];
    }
}
