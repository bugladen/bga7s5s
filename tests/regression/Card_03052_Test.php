<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03052;

class Card_03052_Test extends TestCase
{
    public function name(): string
    {
        return '_03052 Yevgeni';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran hero' => function () {
                $card = new _03052();
                Assert::instanceOf(Character::class, $card, 'character');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::same(6, $card->Resolve, 'resolve');
                Assert::same(4, $card->Combat, 'combat');
                Assert::same(1, $card->Finesse, 'finesse');
                Assert::same(1, $card->Influence, 'influence');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Hero'), 'Hero');
                Assert::true($card->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($card->hasTrait('Scion'), 'Scion');
                Assert::true($card->hasTrait('Exile'), 'Exile');
                Assert::instanceOf(Reaction_03052::class, $card->getReactions()[0], 'reaction');
                Assert::instanceOf(Technique_03052::class, $card->getTechniques()[0], 'technique');
            },
        ];
    }
}
