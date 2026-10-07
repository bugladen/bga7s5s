<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01027_Test extends TestCase
{
    public function name(): string
    {
        return '_01027 Objection!';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Reaction_01027' => function () {
                $card = new _01027();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::true($card->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($card->hasTrait('Cunning'), 'Cunning');
                Assert::instanceOf(Reaction_01027::class, $card->getReactions()[0], 'Reaction_01027');
            },
        ];
    }
}
