<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01130;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01130;

class Card_01130_Test extends TestCase
{
    public function name(): string
    {
        return '_01130 Indomitable Will';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Immovable Provocation Risk with Action_01130' => function () {
                $card = new _01130();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(0, $card->Thrust, 'Thrust');
                Assert::true($card->DashedThrust, 'dashed Thrust');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Immovable'), 'Immovable');
                Assert::true($card->hasTrait('Provocation'), 'Provocation');
                Assert::instanceOf(Action_01130::class, $card->getActions()[0], 'Action_01130');
            },

            'ability ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01130(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id . '_Action_01130', $card->getActions()[0]->Id, 'action id');
            },
        ];
    }
}
