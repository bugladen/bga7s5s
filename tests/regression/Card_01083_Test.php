<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01083;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01083;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01083_Test extends TestCase
{
    public function name(): string
    {
        return '_01083 Legendary Reputation';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk with Action_01083 only' => function () {
                $card = new _01083();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $card, 'targets characters');
                Assert::false($card instanceof IHasManeuvers, 'no maneuvers');
                Assert::same(2, $card->WealthCost, 'WealthCost');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::same(2, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Challenge'), 'Challenge');
                Assert::true($card->hasTrait('Glory'), 'Glory');
                Assert::true($card->hasTrait('Honor'), 'Honor');
                Assert::true($card->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::instanceOf(Action_01083::class, $card->getActions()[0], 'Action_01083');
            },
        ];
    }
}
