<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01108;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01108a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01108b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01108_Test extends TestCase
{
    public function name(): string
    {
        return '_01108 Life in the Canals';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Risk with Scoundrel and Pirate Maneuvers' => function () {
                $card = new _01108();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::true($card->hasFaction('Castille'), 'Castille');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(0, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Cunning'), 'Cunning');
                Assert::true($card->hasTrait('El Punal Occulto'), 'El Punal Occulto');
                Assert::count(2, $card->getManeuvers(), 'two maneuvers');
                Assert::instanceOf(Maneuver_01108a::class, $card->getManeuvers()[0], '01108a');
                Assert::instanceOf(Maneuver_01108b::class, $card->getManeuvers()[1], '01108b');
            },
        ];
    }
}
