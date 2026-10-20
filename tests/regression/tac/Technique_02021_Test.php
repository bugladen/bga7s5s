<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02021;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_02021_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02021';
    }

    /**
     * @return array{0:_02021,1:Technique_02021,2:GenericCharacter}
     */
    private function duel(TestWorld $world, int $foeInfluence = 1): array
    {
        $anghos = $world->placeCharacter(new _02021(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->Influence = $foeInfluence;
        $world->theah->duelActor = $anghos;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Technique_02021 $technique */
        $technique = $anghos->getTechniques()[0];
        return [$anghos, $technique, $foe];
    }

    public function tests(): array
    {
        return [
            'is a Technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_02021(), 'Technique');
            },

            // WHY: "adversary" is in the cost (before •) — duel-only, NOT a challenge Technique.
            'available in duel when Anghos Influence exceeds adversary Influence' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world, 1);
                // Anghos printed Influence = 3 > 1
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            // WHY: compares base Influence (not ModifiedInfluence).
            'unavailable when Anghos Influence is not greater than adversary' => function () {
                $world = new TestWorld();
                [$anghos, $technique, $foe] = $this->duel($world, 3);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'equal');
                $foe->Influence = 4;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'less');
                $anghos->ModifiedInfluence = 10;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'Modified ignored');
            },

            'duel calc adds +1 Parry' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->parry = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->parry, 'parry');
                Assert::count(1, $event->explanations, 'explained');
            },
        ];
    }
}
