<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03068;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03068;

class Card_03068_Test extends TestCase
{
    public function name(): string
    {
        return '_03068 Confusion';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral duress risk' => function () {
                $card = new _03068();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Hubris'), 'Hubris');
                Assert::true($card->hasTrait('Duress'), 'Duress');
                Assert::instanceOf(Reaction_03068::class, $card->getReactions()[0], 'reaction');
            },
        ];
    }
}
