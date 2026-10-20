<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01081;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01081;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01081_Test extends TestCase
{
    public function name(): string
    {
        return '_01081 Gallant Deeds';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk with Action_01081 only' => function () {
                $card = new _01081();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::false($card instanceof IHasManeuvers, 'no maneuvers');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(2, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Heroic'), 'Heroic');
                Assert::true($card->hasTrait('Honor'), 'Honor');
                Assert::true($card->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::instanceOf(Action_01081::class, $card->getActions()[0], 'Action_01081');
            },
        ];
    }
}
