<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01066;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01066;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_01066_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01066';
    }

    /** @return array{0:_01066,1:GenericCharacter,2:Technique_01066} */
    private function duel(TestWorld $world): array
    {
        $horatio = $world->placeCharacter(new _01066(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $horatio;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01066 $technique */
        $technique = $horatio->getTechniques()[0];
        return [$horatio, $foe, $technique];
    }

    public function tests(): array
    {
        return [
            'available when the adversary is the only enemy at the location' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'sole enemy');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no duel');
            },

            'unavailable when there is no duel adversary' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->theah->duelActor = null;
                $world->theah->duelOpponent = null;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no adversary');
            },

            // WHY (journal 2026-03-31): count every opposing character, not just those matching the adversary's controller.
            'unavailable when a second enemy is at the location' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->placeCharacter(new GenericCharacter('Second Foe'), Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'two enemies');
            },

            'friendly characters at the location do not count as enemies' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'ally ignored');
            },

            'enemies at other locations do not count' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->placeCharacter(new GenericCharacter('Far Foe'), Game::LOCATION_CITY_FORUM, 2);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'other location');
            },

            // WHY (journal 2026-09-13 adversary gate): exactly one enemy is not enough - that enemy must BE the adversary.
            'unavailable when the sole enemy at the location is not the adversary' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                // Adversary sits at the location but is uncontrolled, so the only counted enemy is someone else.
                $foe->ControllerId = 0;
                $world->placeCharacter(new GenericCharacter('Other Foe'), Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'wrong sole enemy');
            },

            'unavailable when Horatio is blanked' => function () {
                $world = new TestWorld();
                [$horatio, , $technique] = $this->duel($world);
                $horatio->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'calculate adds 2 Thrust with an explanation' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(2, $event->thrust, '+2 Thrust');
                Assert::same(0, $event->riposte, 'no Riposte');
                Assert::same(0, $event->parry, 'no Parry');
                Assert::count(1, $event->explanations, 'explanation');
            },

            'calculate for another technique is ignored' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = 'someOtherTechnique';
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(0, $event->thrust, 'ignored');
            },
        ];
    }
}
