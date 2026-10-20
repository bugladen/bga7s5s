<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01060_Test extends TestCase
{
    public function name(): string
    {
        return '_01060 Stratege';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01060 and no maneuvers' => function () {
                $card = new _01060();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::false($card instanceof IHasManeuvers, 'no maneuvers');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'DashedRiposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(3, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::true($card->hasTrait('Logistics'), 'Logistics');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::instanceOf(Action_01060::class, $card->getActions()[0], 'Action_01060');
            },
        ];
    }
}
