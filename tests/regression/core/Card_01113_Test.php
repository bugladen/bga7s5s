<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01113;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01113;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01113;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01113_Test extends TestCase
{
    public function name(): string
    {
        return '_01113 Robbery';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Risk with Action and Maneuver' => function () {
                $card = new _01113();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(3, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Crime'), 'Crime');
                Assert::true($card->hasTrait('Theft'), 'Theft');
                Assert::true($card->hasFaction('Castille'), 'Castille');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::instanceOf(Action_01113::class, $card->getActions()[0], 'Action_01113');
                Assert::count(1, $card->getManeuvers(), 'one maneuver');
                Assert::instanceOf(Maneuver_01113::class, $card->getManeuvers()[0], 'Maneuver_01113');
            },

            'ability ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01113(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id . '_Action_01113', $card->getActions()[0]->Id, 'action id');
                Assert::same($card->Id . '_Maneuver_01113', $card->getManeuvers()[0]->Id, 'maneuver id');
            },
        ];
    }
}
