<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04013;

class Card_04013_Test extends TestCase
{
    public function name(): string
    {
        return '_04013 Tomas E Spielen';
    }

    public function tests(): array
    {
        return [
            'is an Eisen academic with a reaction and a technique' => function () {
                $card = new _04013();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(5, $card->Resolve, 'resolve');
                Assert::true($card->hasTrait('Antiquarian'), 'Antiquarian');
                Assert::true($card->hasTrait('Academic'), 'Academic');
                Assert::count(1, $card->getReactions(), 'reaction');
                Assert::count(1, $card->getTechniques(), 'technique');
            },
        ];
    }
}
