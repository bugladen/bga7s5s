<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd18;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03cd18;

class Card_03cd18_Test extends TestCase
{
    public function name(): string
    {
        return '_03cd18 Kalla and Adelheide';
    }

    public function tests(): array
    {
        return [
            'constructs a Negotiable Academic Artisan with the recruit Reaction' => function () {
                $card = new _03cd18();
                Assert::instanceOf(CityCharacter::class, $card, 'CityCharacter');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::same(18, $card->CityCardNumber, 'city number');
                Assert::same(5, $card->WealthCost, 'wealth');
                Assert::same(4, $card->Resolve, 'Resolve');
                Assert::same(3, $card->Combat, 'Combat');
                Assert::same(2, $card->Finesse, 'Finesse');
                Assert::true($card->DashedInfluence, 'dashed Influence');
                Assert::true($card->Negotiable, 'negotiable');
                Assert::true($card->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($card->hasTrait('Academic'), 'Academic');
                Assert::true($card->hasTrait('Artisan'), 'Artisan');
                Assert::true($card->hasTrait('Eisen'), 'Eisen');
                Assert::true($card->hasTrait('Vesten'), 'Vesten');
                Assert::count(1, $card->getReactions(), 'one Reaction');
                Assert::instanceOf(Reaction_03cd18::class, $card->getReactions()[0], 'Reaction_03cd18');
            },
        ];
    }
}
