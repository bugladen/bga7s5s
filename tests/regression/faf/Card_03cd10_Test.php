<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd10;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03cd10;

class Card_03cd10_Test extends TestCase
{
    public function name(): string
    {
        return '_03cd10 Julius Caligari';
    }

    public function tests(): array
    {
        return [
            'constructs a Negotiable Mercenary Villain with the trait Reaction' => function () {
                $julius = new _03cd10();
                Assert::instanceOf(CityCharacter::class, $julius, 'CityCharacter');
                Assert::instanceOf(IHasReactions::class, $julius, 'reactions');
                Assert::same(10, $julius->CityCardNumber, 'city number');
                Assert::same(5, $julius->WealthCost, 'wealth');
                Assert::same(4, $julius->Resolve, 'Resolve');
                Assert::same(1, $julius->Combat, 'Combat');
                Assert::same(3, $julius->Finesse, 'Finesse');
                Assert::same(2, $julius->Influence, 'Influence');
                Assert::true($julius->Negotiable, 'negotiable');
                Assert::true($julius->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($julius->hasTrait('Villain'), 'Villain');
                Assert::true($julius->hasTrait('Spy'), 'Spy');
                Assert::true($julius->hasTrait('Vodacce'), 'Vodacce');
                Assert::count(1, $julius->getReactions(), 'one Reaction');
                Assert::instanceOf(Reaction_03cd10::class, $julius->getReactions()[0], 'Reaction_03cd10');
            },
        ];
    }
}
