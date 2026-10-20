<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01138;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01138;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01138_Test extends TestCase
{
    public function name(): string
    {
        return '_01138 Razrushitel';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Risk that targets characters' => function () {
                $card = new _01138();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $card, 'targets characters');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(4, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Brawl'), 'Brawl');
                Assert::true($card->hasTrait('Hunt'), 'Hunt');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::instanceOf(Action_01138::class, $card->getActions()[0], 'Action_01138');
            },

            'ability id is stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01138(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id . '_Action_01138', $card->getActions()[0]->Id, 'action id');
            },
        ];
    }
}
