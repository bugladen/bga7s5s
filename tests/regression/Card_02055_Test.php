<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02055;

class Card_02055_Test extends TestCase
{
    public function name(): string
    {
        return '_02055 Dame of Swords';
    }

    public function tests(): array
    {
        return [
            'constructs a Neutral Relic with dashed Riposte' => function () {
                $card = new _02055();
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::instanceOf(IHasTechniques::class, $card, 'techniques');
                Assert::same('Favored Fortune', $card->Title, 'title');
                Assert::same(0, $card->WealthCost, 'Wealth');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(3, $card->Thrust, 'Thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Trinket'), 'Trinket');
                Assert::true($card->hasTrait('Card'), 'Card');
                Assert::true($card->hasTrait('Relic'), 'Relic');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Technique_02055::class, $card->getTechniques()[0], 'technique');
            },

            'owns the technique when placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _02055(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id, $card->getTechniques()[0]->OwnerId, 'owner');
            },
        ];
    }
}
