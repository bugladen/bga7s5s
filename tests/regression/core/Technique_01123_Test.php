<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01123;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01123;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_01123_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01123';
    }

    /** @return array{0:_01123,1:GenericCharacter,2:Technique_01123} */
    private function duel(TestWorld $world): array
    {
        $valeri = $world->placeCharacter(new _01123(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $valeri;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01123 $technique */
        $technique = $valeri->getTechniques()[0];
        return [$valeri, $foe, $technique];
    }

    private function calculate(TestWorld $world, Technique_01123 $technique, int $actorId, int $adversaryId): EventDuelCalculateTechniqueValues
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
            // WHY: fewer wounds than adversary → +1 Riposte instead of +1 Thrust.
            'fewer wounds than the adversary grants +1 Riposte' => function () {
                $world = new TestWorld();
                [$valeri, $foe, $technique] = $this->duel($world);
                $valeri->Wounds = 0;
                $foe->Wounds = 2;

                $event = $this->calculate($world, $technique, $valeri->Id, $foe->Id);

                Assert::same(1, $event->riposte, '+1 Riposte');
                Assert::same(0, $event->thrust, 'no Thrust');
            },

            'equal or more wounds grants +1 Thrust' => function () {
                $world = new TestWorld();
                [$valeri, $foe, $technique] = $this->duel($world);
                $valeri->Wounds = 2;
                $foe->Wounds = 2;

                $event = $this->calculate($world, $technique, $valeri->Id, $foe->Id);

                Assert::same(1, $event->thrust, '+1 Thrust');
                Assert::same(0, $event->riposte, 'no Riposte');
            },

            'more wounds than the adversary still grants +1 Thrust' => function () {
                $world = new TestWorld();
                [$valeri, $foe, $technique] = $this->duel($world);
                $valeri->Wounds = 3;
                $foe->Wounds = 1;

                $event = $this->calculate($world, $technique, $valeri->Id, $foe->Id);

                Assert::same(1, $event->thrust, '+1 Thrust');
            },

            'challenge threat adds 1' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->adversaryThreat, '+1 Threat');
            },

            'challenge threat for another technique is ignored' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = 'someOtherTechnique';
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(0, $event->adversaryThreat, 'ignored');
            },
        ];
    }
}
