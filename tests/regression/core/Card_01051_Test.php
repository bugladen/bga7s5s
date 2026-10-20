<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01051_Test extends TestCase
{
    public function name(): string
    {
        return '_01051 Answering the Call';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Maneuver_01051 only' => function () {
                $card = new _01051();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::false($card instanceof IHasActions, 'no actions');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::same(3, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::true($card->hasTrait('Duty'), 'Duty');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::count(1, $card->getManeuvers(), 'one maneuver');
                Assert::instanceOf(Maneuver_01051::class, $card->getManeuvers()[0], 'Maneuver_01051');
            },
        ];
    }
}