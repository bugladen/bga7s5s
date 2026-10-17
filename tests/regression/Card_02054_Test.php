<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02054;

class Card_02054_Test extends TestCase
{
    public function name(): string
    {
        return '_02054 Concealed Flintlock';
    }

    public function tests(): array
    {
        return [
            'constructs a Neutral Offhand pistol' => function () {
                $card = new _02054();
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::instanceOf(IHasTechniques::class, $card, 'techniques');
                Assert::true($card->OffHand, 'Offhand');
                Assert::same(1, $card->WealthCost, 'Wealth');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Weapon'), 'Weapon');
                Assert::true($card->hasTrait('Ranged'), 'Ranged');
                Assert::true($card->hasTrait('Pistol'), 'Pistol');
                Assert::true($card->hasTrait('Stealth'), 'Stealth');
                Assert::instanceOf(Maneuver_02054::class, $card->getManeuvers()[0], 'maneuver');
                Assert::instanceOf(Technique_02054::class, $card->getTechniques()[0], 'technique');
            },

            'owns the maneuver and technique when placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _02054(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id, $card->getManeuvers()[0]->OwnerId, 'maneuver');
                Assert::same($card->Id, $card->getTechniques()[0]->OwnerId, 'technique');
                Assert::same('Maneuver_02054', $card->getManeuvers()[0]->ClassId, 'maneuver class');
                Assert::same('Technique_02054', $card->getTechniques()[0]->ClassId, 'technique class');
            },
        ];
    }
}
