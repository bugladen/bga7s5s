<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01129;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01129;

class Card_01129_Test extends TestCase
{
    public function name(): string
    {
        return '_01129 Borets';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Sorcery Porte Risk with Maneuver_01129' => function () {
                $card = new _01129();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(2, $card->Thrust, 'Thrust');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($card->hasTrait('Porte'), 'Porte');
                Assert::instanceOf(Maneuver_01129::class, $card->getManeuvers()[0], 'Maneuver_01129');
            },

            'ability ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01129(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id . '_Maneuver_01129', $card->getManeuvers()[0]->Id, 'maneuver id');
            },
        ];
    }
}
