<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01076;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01076;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01076_Test extends TestCase
{
    public function name(): string
    {
        return '_01076 Blood Mark';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk with Action_01076 only' => function () {
                $card = new _01076();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::false($card instanceof IHasManeuvers, 'no maneuvers');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(0, $card->Thrust, 'Thrust');
                Assert::true($card->DashedThrust, 'dashed Thrust');
                Assert::true($card->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($card->hasTrait('Porte'), 'Porte');
                Assert::true($card->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::instanceOf(Action_01076::class, $card->getActions()[0], 'Action_01076');
            },
        ];
    }
}
