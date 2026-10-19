<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04003;

class Card_04003_Test extends TestCase
{
    public function name(): string
    {
        return '_04003 Desideria Colomberia';
    }

    public function tests(): array
    {
        return [
            'is a Strega with two reactions' => function () {
                $card = new _04003();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(3, $card->CardNumber, 'number');
                Assert::same(5, $card->Resolve, 'resolve');
                Assert::true($card->hasTrait('Strega'), 'Strega');
                Assert::true($card->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::count(2, $card->getReactions(), 'reactions');
            },
        ];
    }
}
