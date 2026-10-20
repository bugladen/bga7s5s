<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01058_Test extends TestCase
{
    public function name(): string
    {
        return '_01058 Press the Advantage';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01058 and Maneuver_01058' => function () {
                $card = new _01058();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $card, 'targets characters');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::same(2, $card->WealthCost, 'WealthCost');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Relentless'), 'Relentless');
                Assert::true($card->hasTrait('Drexel'), 'Drexel');
                Assert::instanceOf(Action_01058::class, $card->getActions()[0], 'Action_01058');
                Assert::instanceOf(Maneuver_01058::class, $card->getManeuvers()[0], 'Maneuver_01058');
            },
        ];
    }
}
