<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01056_Test extends TestCase
{
    public function name(): string
    {
        return '_01056 Move Along';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01056 only' => function () {
                $card = new _01056();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $card, 'targets characters');
                // WHY: City Action only — no Maneuver on the printed card.
                Assert::false($card instanceof IHasManeuvers, 'no maneuvers');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::same(4, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Challenge'), 'Challenge');
                Assert::true($card->hasTrait('Provocation'), 'Provocation');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::instanceOf(Action_01056::class, $card->getActions()[0], 'Action_01056');
            },
        ];
    }
}