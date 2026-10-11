<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01196;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01196;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_01196_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01196';
    }

    /** @return array{0:_01196,1:GenericCharacter,2:Technique_01196} */
    private function duel(TestWorld $world): array
    {
        $angeline = $world->placeCharacter(new _01196(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $angeline;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01196 $technique */
        $technique = $angeline->getTechniques()[0];
        return [$angeline, $foe, $technique];
    }

    private function calculate(TestWorld $world, Technique_01196 $technique, int $actorId, int $adversaryId): EventDuelCalculateTechniqueValues
    {
        $event = new EventDuelCalculateTechniqueValues();
        $event->techniqueId = $technique->Id;
        $event->actorId = $actorId;
        $event->adversaryId = $adversaryId;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY (journal 2026-04-01-11): each stat individually >=, not sum.
            'equal or greater Combat AND Influence grants +1 Riposte' => function () {
                $world = new TestWorld();
                [$angeline, $foe, $technique] = $this->duel($world);
                // Angeline printed 3/2; foe defaults 1/1
                $event = $this->calculate($world, $technique, $angeline->Id, $foe->Id);

                Assert::same(1, $event->riposte, '+1 Riposte');
                Assert::same(0, $event->thrust, 'no Thrust');
            },

            'equal Combat and Influence still grants +1 Riposte' => function () {
                $world = new TestWorld();
                [$angeline, $foe, $technique] = $this->duel($world);
                $foe->ModifiedCombat = 3;
                $foe->ModifiedInfluence = 2;

                $event = $this->calculate($world, $technique, $angeline->Id, $foe->Id);

                Assert::same(1, $event->riposte, 'equal ok');
            },

            // WHY: sum-check bug — Combat 5/Inf 1 vs Combat 2/Inf 3 passes on sum but Inf fails.
            'higher Combat cannot compensate for lower Influence' => function () {
                $world = new TestWorld();
                [$angeline, $foe, $technique] = $this->duel($world);
                $angeline->ModifiedCombat = 5;
                $angeline->ModifiedInfluence = 1;
                $foe->ModifiedCombat = 2;
                $foe->ModifiedInfluence = 3;

                $event = $this->calculate($world, $technique, $angeline->Id, $foe->Id);

                Assert::same(0, $event->riposte, 'no Riposte');
            },

            'lower Combat blocks even with higher Influence' => function () {
                $world = new TestWorld();
                [$angeline, $foe, $technique] = $this->duel($world);
                $angeline->ModifiedCombat = 1;
                $angeline->ModifiedInfluence = 5;
                $foe->ModifiedCombat = 2;
                $foe->ModifiedInfluence = 1;

                $event = $this->calculate($world, $technique, $angeline->Id, $foe->Id);

                Assert::same(0, $event->riposte, 'blocked');
            },

            'available in duel when Angeline is actor and beats both stats' => function () {
                $world = new TestWorld();
                [$angeline, , $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'unavailable when Angeline is not the duel actor' => function () {
                $world = new TestWorld();
                [$angeline, $foe, $technique] = $this->duel($world);
                $world->theah->duelActor = $foe;
                $world->theah->duelOpponent = $angeline;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not actor');
            },

            'unavailable when Influence is lower than the adversary' => function () {
                $world = new TestWorld();
                [$angeline, $foe, $technique] = $this->duel($world);
                $foe->ModifiedInfluence = $angeline->ModifiedInfluence + 1;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'Inf short');
            },

            'challenge threat adds 1 when both stats qualify' => function () {
                $world = new TestWorld();
                [$angeline, $foe, $technique] = $this->duel($world);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->actorId = $angeline->Id;
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->adversaryThreat, '+1 Threat');
            },

            'challenge threat skips when Influence is short' => function () {
                $world = new TestWorld();
                [$angeline, $foe, $technique] = $this->duel($world);
                $foe->ModifiedInfluence = $angeline->ModifiedInfluence + 1;

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->actorId = $angeline->Id;
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(0, $event->adversaryThreat, 'no threat');
            },

            'challenge threat for another technique is ignored' => function () {
                $world = new TestWorld();
                [$angeline, $foe, $technique] = $this->duel($world);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = 'someOtherTechnique';
                $event->actorId = $angeline->Id;
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(0, $event->adversaryThreat, 'ignored');
            },
        ];
    }
}
