<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01140;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01140;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01140_Test extends TestCase
{
    public function name(): string
    {
        return '_01140 Stubborn';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Hubris Risk with Reaction' => function () {
                $card = new _01140();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(3, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Hubris'), 'Hubris');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::instanceOf(Reaction_01140::class, $card->getReactions()[0], 'Reaction_01140');
            },

            'ability id is stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01140(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id . '_Reaction_01140', $card->getReactions()[0]->Id, 'reaction id');
            },
        ];
    }
}
