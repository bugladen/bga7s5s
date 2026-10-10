<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01136;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01136;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01136;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01136_Test extends TestCase
{
    public function name(): string
    {
        return '_01136 My Fight, Alone';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Risk with Action and Maneuver' => function () {
                $card = new _01136();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Relentless'), 'Relentless');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::instanceOf(Action_01136::class, $card->getActions()[0], 'Action_01136');
                Assert::instanceOf(Maneuver_01136::class, $card->getManeuvers()[0], 'Maneuver_01136');
            },

            'ability ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01136(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id . '_Action_01136', $card->getActions()[0]->Id, 'action id');
                Assert::same($card->Id . '_Maneuver_01136', $card->getManeuvers()[0]->Id, 'maneuver id');
            },
        ];
    }
}
