<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01127;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01127;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusTwoThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Card_01127_Test extends TestCase
{
    public function name(): string
    {
        return "_01127 Grandfather's Hammer";
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Unique Hammer FactionAttachment' => function () {
                $hammer = new _01127();
                Assert::instanceOf(FactionAttachment::class, $hammer, 'FactionAttachment');
                Assert::instanceOf(IHasReactions::class, $hammer, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $hammer, 'techniques');
                Assert::true($hammer->hasFaction('Ussura'), 'Ussura');
                Assert::true($hammer->hasTrait('Weapon'), 'Weapon');
                Assert::true($hammer->hasTrait('Melee'), 'Melee');
                Assert::true($hammer->hasTrait('Hammer'), 'Hammer');
                Assert::true($hammer->hasTrait('Unique'), 'Unique');
            },

            // WHY: FactionAttachment pre-commit requires Riposte set; pin dashed Riposte + duel line.
            'costs 2 Wealth with dashed Riposte 0, Parry 1, Thrust 5' => function () {
                $hammer = new _01127();
                Assert::same(2, $hammer->WealthCost, 'wealth');
                Assert::same(0, $hammer->Riposte, 'Riposte');
                Assert::true($hammer->DashedRiposte, 'dashed Riposte');
                Assert::same(1, $hammer->Parry, 'Parry');
                Assert::same(5, $hammer->Thrust, 'Thrust');
                Assert::same(0, $hammer->ResolveModifier, 'Resolve');
                Assert::same(0, $hammer->CombatModifier, 'Combat');
                Assert::same(0, $hammer->FinesseModifier, 'Finesse');
                Assert::same(0, $hammer->InfluenceModifier, 'Influence');
            },

            // WHY: Shared Technique_PlusTwoThrust is re-Id'd to Technique_01127 so duel code
            // can tell which card's technique fired. Ownership prefix is added on placement.
            'has Reaction_01127 and Technique_PlusTwoThrust ClassId Technique_01127' => function () {
                $unplaced = new _01127();
                Assert::same('Technique_01127', $unplaced->getTechniques()[0]->ClassId, 'ClassId');

                $world = new TestWorld();
                $hammer = $world->placeCard(new _01127(), Game::LOCATION_HAND, 1);

                Assert::count(1, $hammer->getReactions(), 'one reaction');
                Assert::instanceOf(Reaction_01127::class, $hammer->getReactions()[0], 'Reaction_01127');
                Assert::same($hammer->Id, $hammer->getReactions()[0]->OwnerId, 'reaction owner');

                Assert::count(1, $hammer->getTechniques(), 'one technique');
                /** @var Technique_PlusTwoThrust $technique */
                $technique = $hammer->getTechniques()[0];
                Assert::instanceOf(Technique_PlusTwoThrust::class, $technique, 'PlusTwoThrust');
                Assert::same("{$hammer->Id}_Technique_01127", $technique->Id, 'owner-prefixed Id');
                Assert::same($hammer->Id, $technique->OwnerId, 'technique owner');
            },

            'starts unattached' => function () {
                Assert::false((new _01127())->isAttached(), 'not attached');
            },

            // WHY: shared Technique_PlusTwoThrust re-Id'd per card — cover +2 Thrust / challenge threat here.
            'Technique_01127 adds +2 Thrust on calculate' => function () {
                $world = new TestWorld();
                $hammer = $world->placeCard(new _01127(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_PlusTwoThrust $technique */
                $technique = $hammer->getTechniques()[0];

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->thrust = 1;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(3, $event->thrust, '+2 Thrust');
                Assert::count(1, $event->explanations, 'explained');
            },

            'Technique_01127 adds +2 adversary threat on challenge' => function () {
                $world = new TestWorld();
                $hammer = $world->placeCard(new _01127(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_PlusTwoThrust $technique */
                $technique = $hammer->getTechniques()[0];

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->actorId = 0;
                $event->adversaryThreat = 0;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(2, $event->adversaryThreat, '+2 threat');
            },
        ];
    }
}
