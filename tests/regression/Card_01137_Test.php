<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01137;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01137;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01137_Test extends TestCase
{
    public function name(): string
    {
        return '_01137 Predatory Pursuit';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Hunt Risk with Reaction' => function () {
                $card = new _01137();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'dashed Riposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::same(4, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Hunt'), 'Hunt');
                Assert::true($card->hasTrait('Relentless'), 'Relentless');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::instanceOf(Reaction_01137::class, $card->getReactions()[0], 'Reaction_01137');
            },

            'ability id is stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01137(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id . '_Reaction_01137', $card->getReactions()[0]->Id, 'reaction id');
            },
        ];
    }
}
