<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04023;

class Card_04023_Test extends TestCase
{
    public function name(): string
    {
        return '_04023 Monet Dantès';
    }

    public function tests(): array
    {
        return [
            'is a duelist merchant pirate' => function () {
                $card = new _04023();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(4, $card->Resolve, 'resolve');
                Assert::same(1, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Duelist'), 'Duelist');
                Assert::true($card->hasTrait('Merchant'), 'Merchant');
                Assert::true($card->hasTrait('Pirate'), 'Pirate');
                Assert::count(1, $card->getReactions(), 'reaction');
            },
        ];
    }
}
