<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01114;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01114;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01114_Test extends TestCase
{
    public function name(): string
    {
        return '_01114 Roll the Bones';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Risk with Maneuver only' => function () {
                $card = new _01114();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::false($card instanceof IHasActions, 'no actions');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(0, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Cheating'), 'Cheating');
                Assert::true($card->hasFaction('Castille'), 'Castille');
                Assert::count(1, $card->getManeuvers(), 'one maneuver');
                Assert::instanceOf(Maneuver_01114::class, $card->getManeuvers()[0], 'Maneuver_01114');
            },

            'maneuver id is stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01114(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id . '_Maneuver_01114', $card->getManeuvers()[0]->Id, 'maneuver id');
            },
        ];
    }
}
