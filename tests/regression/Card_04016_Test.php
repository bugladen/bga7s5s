<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04016;

class Card_04016_Test extends TestCase
{
    public function name(): string
    {
        return '_04016 Drachenblut';
    }

    public function tests(): array
    {
        return [
            'is an unguent with a reaction and a gambling technique' => function () {
                $card = new _04016();
                Assert::instanceOf(FactionAttachment::class, $card, 'FactionAttachment');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::true($card->hasTrait('Unguent'), 'Unguent');
                Assert::count(1, $card->getReactions(), 'reaction');
                Assert::count(1, $card->getTechniques(), 'technique');
            },
        ];
    }
}
