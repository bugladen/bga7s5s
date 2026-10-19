<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04031;

class Card_04031_Test extends TestCase
{
    public function name(): string
    {
        return '_04031 Andare de Castillo';
    }

    public function tests(): array
    {
        return [
            'is a zealot duelist' => function () {
                $card = new _04031();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(5, $card->Resolve, 'resolve');
                Assert::same(2, $card->Combat, 'combat');
                Assert::true($card->hasTrait('Zealot'), 'Zealot');
                Assert::true($card->hasTrait('Duelist'), 'Duelist');
                Assert::count(1, $card->getReactions(), 'reaction');
                Assert::count(1, $card->getTechniques(), 'technique');
            },
        ];
    }
}
