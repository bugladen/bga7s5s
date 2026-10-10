<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01157;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01157;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_DestroyPlusOneThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;

class Technique_01157_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01157';
    }

    /**
     * Throwing Knife on host. Destroy/+1 Thrust from parent; ranged from subclass.
     *
     * @return array{0:_01157,1:GenericCharacter,2:Technique_01157}
     */
    private function equip(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $knife = $world->placeCard(new _01157(), Game::LOCATION_CITY_DOCKS, 1);
        $knife->AttachedToId = $host->Id;
        $host->Attachments[] = $knife->Id;
        /** @var Technique_01157 $technique */
        $technique = $knife->getTechniques()[0];
        return [$knife, $host, $technique];
    }

    public function tests(): array
    {
        return [
            // WHY: Subclass exists only for IRangedAbility + RangedAbilityPlayed — destroy/thrust
            // live on Technique_DestroyPlusOneThrust (journal 2026-06-01-03).
            'extends DestroyPlusOneThrust and is ranged' => function () {
                $technique = new Technique_01157();
                Assert::instanceOf(Technique_DestroyPlusOneThrust::class, $technique, 'parent');
                Assert::instanceOf(IRangedAbility::class, $technique, 'ranged');
            },

            'available to the host controller when attached' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->equip($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'controller');
            },

            'unavailable to the opponent' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->equip($world);
                Assert::false($technique->isAvailableToPlayer(2, $world->theah), 'opponent');
            },

            'unavailable when the knife is not attached' => function () {
                $world = new TestWorld();
                $knife = $world->placeCard(new _01157(), Game::LOCATION_HAND, 1);
                /** @var Technique_01157 $technique */
                $technique = $knife->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'unattached');
            },

            'duel calculate adds +1 Thrust, destroys the knife, and fires RangedAbilityPlayed' => function () {
                $world = new TestWorld();
                [$knife, $host, $technique] = $this->equip($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->actorId = $host->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->thrust, '+1 Thrust');
                Assert::count(1, $event->explanations, 'explained');
                $unequips = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequips, 'unequip');
                Assert::same($knife->Id, $unequips[0]->attachmentId, 'knife');
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'discard');
                $ranged = $world->theah->queuedOfType(EventRangedAbilityPlayed::class);
                Assert::count(1, $ranged, 'ranged');
                Assert::same($knife->Id, $ranged[0]->sourceId, 'source');
                Assert::same($technique->Id, $ranged[0]->abilityId, 'ability');
            },

            'challenge threat (not preview) adds +1 adversary threat, destroys, and fires ranged' => function () {
                $world = new TestWorld();
                [$knife, $host, $technique] = $this->equip($world);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->actorId = $host->Id;
                $event->preview = false;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->adversaryThreat, '+1 threat');
                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'unequip');
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'discard');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
                Assert::same($knife->Id, $world->theah->queuedOfType(EventRangedAbilityPlayed::class)[0]->sourceId, 'source');
            },

            // WHY: Accept-challenge args dry-run must not destroy; subclass also gates ranged on !preview.
            'challenge threat preview adds threat without destroy or ranged' => function () {
                $world = new TestWorld();
                [, $host, $technique] = $this->equip($world);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->actorId = $host->Id;
                $event->preview = true;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->adversaryThreat, '+1 threat');
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'no unequip');
                Assert::count(0, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'no ranged');
            },

            'events for another technique id are ignored' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->equip($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = 'other';
                $event->thrust = 5;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(5, $event->thrust, 'untouched');
                Assert::count(0, $world->theah->queuedEvents, 'no side effects');
            },
        ];
    }
}
