<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01087;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01087;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01087;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01087_Test extends TestCase
{
    public function name(): string
    {
        return '_01087 Valiant Spirit';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk with Action_01087 and Maneuver_01087' => function () {
                $card = new _01087();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(2, $card->WealthCost, 'WealthCost');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::same(2, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Duty'), 'Duty');
                Assert::true($card->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::count(1, $card->getManeuvers(), 'one maneuver');
                Assert::instanceOf(Action_01087::class, $card->getActions()[0], 'Action_01087');
                Assert::instanceOf(Maneuver_01087::class, $card->getManeuvers()[0], 'Maneuver_01087');
            },
        ];
    }
}
