<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01155;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01155;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_DestroyPlusOneThrust;

class Card_01155_Test extends TestCase
{
    public function name(): string
    {
        return '_01155 Improvised Weapon';
    }

    public function tests(): array
    {
        return [
            'constructs Flourish Weapon with Technique, Maneuver, and Reaction' => function () {
                $weapon = new _01155();
                Assert::instanceOf(FactionAttachment::class, $weapon, 'FactionAttachment');
                Assert::instanceOf(IHasTechniques::class, $weapon, 'techniques');
                Assert::instanceOf(IHasManeuvers::class, $weapon, 'maneuvers');
                Assert::instanceOf(IHasReactions::class, $weapon, 'reactions');
                Assert::same(0, $weapon->WealthCost, 'wealth');
                Assert::true($weapon->DashedRiposte, 'dashed Riposte');
                Assert::same(1, $weapon->Parry, 'Parry');
                Assert::same(1, $weapon->Thrust, 'Thrust');
                Assert::true($weapon->hasTrait('Flourish'), 'Flourish');
                Assert::true($weapon->hasTrait('Weapon'), 'Weapon');
                Assert::true($weapon->hasTrait('Melee'), 'Melee');
                Assert::true($weapon->hasTrait('Ad Hoc'), 'Ad Hoc');
                Assert::instanceOf(Technique_DestroyPlusOneThrust::class, $weapon->getTechniques()[0], 'technique');
                Assert::instanceOf(Maneuver_PlusOneParry::class, $weapon->getManeuvers()[0], 'maneuver');
                Assert::instanceOf(Reaction_01155::class, $weapon->getReactions()[0], 'reaction');
            },

            // WHY: shared Technique/Maneuver classes are re-id'd to Technique_01155 / Maneuver_01155
            // so duel lookups and cancel paths key the Improvised Weapon instance, not a generic id.
            'shared Technique and Maneuver ClassIds are Technique_01155 and Maneuver_01155' => function () {
                $weapon = new _01155();
                Assert::same('Technique_01155', $weapon->getTechniques()[0]->ClassId, 'technique class id');
                Assert::same('Maneuver_01155', $weapon->getManeuvers()[0]->ClassId, 'maneuver class id');
            },

            'ability ids stamped with owner id once placed' => function () {
                $world = new TestWorld();
                $weapon = $world->placeCard(new _01155(), Game::LOCATION_HAND, 1);
                Assert::same($weapon->Id . '_Technique_01155', $weapon->getTechniques()[0]->Id, 'technique');
                Assert::same($weapon->Id . '_Maneuver_01155', $weapon->getManeuvers()[0]->Id, 'maneuver');
                Assert::same($weapon->Id . '_Reaction_01155', $weapon->getReactions()[0]->Id, 'reaction');
            },
        ];
    }
}
