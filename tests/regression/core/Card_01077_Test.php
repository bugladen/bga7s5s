<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01077;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01077;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01077_Test extends TestCase
{
    public function name(): string
    {
        return '_01077 Broken-Time';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk with Maneuver_01077 only' => function () {
                $card = new _01077();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::false($card instanceof IHasActions, 'no actions');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(0, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Valroux'), 'Valroux');
                Assert::true($card->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $card->getManeuvers(), 'one maneuver');
                Assert::instanceOf(Maneuver_01077::class, $card->getManeuvers()[0], 'Maneuver_01077');
            },
        ];
    }
}
