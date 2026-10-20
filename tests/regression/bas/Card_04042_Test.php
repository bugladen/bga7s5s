<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04042;

class Card_04042_Test extends TestCase
{
    public function name(): string
    {
        return '_04042 Kaj Kousei';
    }

    public function tests(): array
    {
        return [
            'is a numa explorer' => function () {
                $card = new _04042();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(4, $card->Resolve, 'resolve');
                Assert::same(1, $card->Combat, 'combat');
                Assert::same(2, $card->Finesse, 'finesse');
                Assert::same(2, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Academic'), 'Academic');
                Assert::true($card->hasTrait('Explorer'), 'Explorer');
                Assert::true($card->hasTrait('Numa'), 'Numa');
                Assert::count(1, $card->getActions(), 'action');
                Assert::count(1, $card->getReactions(), 'reaction');
            },
        ];
    }
}
