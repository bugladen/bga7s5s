<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01078;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01078;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01078_Test extends TestCase
{
    public function name(): string
    {
        return '_01078 Defending Honor';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk that targets characters with Action_01078 only' => function () {
                $card = new _01078();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $card, 'targets characters');
                Assert::false($card instanceof IHasManeuvers, 'no maneuvers');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(3, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Challenge'), 'Challenge');
                Assert::true($card->hasTrait('Provocation'), 'Provocation');
                Assert::true($card->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::instanceOf(Action_01078::class, $card->getActions()[0], 'Action_01078');
            },
        ];
    }
}
