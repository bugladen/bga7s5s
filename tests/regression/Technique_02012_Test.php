<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02012;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_02012_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02012';
    }

    /**
     * @return array{0:_02012,1:Technique_02012}
     */
    private function duel(TestWorld $world): array
    {
        $turais = $world->placeCharacter(new _02012(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $turais;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Technique_02012 $technique */
        $technique = $turais->getTechniques()[0];
        return [$turais, $technique];
    }

    public function tests(): array
    {
        return [
            'is a Technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_02012(), 'Technique');
            },

            // WHY: Berserker Technique — duel + Berserker + non-lethal self-wound gate.
            'available in duel as Berserker when wound would not be lethal' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'unavailable when wound would be lethal' => function () {
                $world = new TestWorld();
                [$turais, $technique] = $this->duel($world);
                $turais->Wounds = 3;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'lethal');
            },

            'unavailable without Berserker' => function () {
                $world = new TestWorld();
                [$turais, $technique] = $this->duel($world);
                $turais->removeTrait($world->game, 'Berserker');
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no Berserker');
            },

            'resolve queues self-wound' => function () {
                $world = new TestWorld();
                [$turais, $technique] = $this->duel($world);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($turais->Id, $wounds[0]->characterId, 'self');
                Assert::same(1, $wounds[0]->wounds, 'one');
            },

            // WHY: "Remove all threat" is modeled as Parry += getCurrentDuelThreat.
            'duel calc adds Parry equal to current duel threat' => function () {
                $world = new TestWorld();
                [$turais, $technique] = $this->duel($world);
                $world->theah->duelThreats[$turais->Id] = 3;

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->parry = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(3, $event->parry, 'parry = threat');
                Assert::count(1, $event->explanations, 'explained');
            },
        ];
    }
}
