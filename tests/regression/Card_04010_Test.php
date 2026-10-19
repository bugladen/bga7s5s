<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04010;

class Card_04010_Test extends TestCase
{
    public function name(): string
    {
        return '_04010 Unravel the Thread';
    }

    public function tests(): array
    {
        return [
            'is a dashed-combat sorte risk' => function () {
                $card = new _04010();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::true($card->DashedParry, 'dashed parry');
                Assert::true($card->DashedThrust, 'dashed thrust');
                Assert::same(2, $card->Riposte, 'riposte');
                Assert::count(1, $card->getActions(), 'action');
                Assert::count(1, $card->getReactions(), 'reaction');
            },

            // WHY: Faction cards are not in buildCity. actFromCard* pins this instance
            // so a later setUsed writes the object the player is holding.
            'acting from the card pins this instance into the world' => function () {
                $world = new TestWorld();
                $card = new _04010();
                $card->setId(4242);
                $card->ControllerId = 1;
                $card->actFromCardPass($world->game, 0, '', '');
                Assert::same($card, $world->theah->getCardById(4242), 'pinned');
            },
        ];
    }
}
