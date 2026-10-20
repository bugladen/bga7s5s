<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01055_Test extends TestCase
{
    public function name(): string
    {
        return '_01055 Last Word';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01055 and Maneuver_01055' => function () {
                $card = new _01055();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $card, 'targets characters');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::same(2, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Ranged'), 'Ranged');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Action_01055::class, $card->getActions()[0], 'Action_01055');
                Assert::instanceOf(Maneuver_01055::class, $card->getManeuvers()[0], 'Maneuver_01055');
            },
        ];
    }
}