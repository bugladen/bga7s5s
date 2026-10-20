<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd29;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04cd29;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_04cd29_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04cd29';
    }

    /**
     * @return array{0:TestWorld,1:_04cd29,2:Technique_04cd29,3:GenericCharacter}
     */
    private function duel(): array
    {
        $world = new TestWorld();
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->game->globals->set(Game::DUEL_GAMBLED, true);
        $tijani = $world->placeCharacter(new _04cd29(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $tijani;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_04cd29 $technique */
        $technique = $tijani->getTechniques()[0];
        return [$world, $tijani, $technique, $foe];
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling technique. He must be the round actor, must have gambled,
            // and the adversary Finesse must be strictly lower. Blanked text hides it.
            'available only for gambled Tijani against a lower-finesse adversary' => function () {
                [$world, $tijani, $technique, $foe] = $this->duel();
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'ready');

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in a duel');
                $world->game->globals->set(Game::IN_DUEL, true);

                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'did not gamble');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);

                $world->theah->duelActor = $foe;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not the actor');
                $world->theah->duelActor = $tijani;

                $world->theah->duelOpponent = null;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no adversary');
                $world->theah->duelOpponent = $foe;

                $foe->ModifiedFinesse = 3;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'finesse not lower');
                $foe->ModifiedFinesse = 1;

                $tijani->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'calculating the technique wounds the adversary and marks it used' => function () {
                [$world, $tijani, $technique, $foe] = $this->duel();
                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);

                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($foe->Id, $wound->characterId, 'adversary');
                Assert::same($tijani->Id, $wound->sourceId, 'tijani');
                Assert::same($technique->Id, $wound->abilityId, 'technique');
                Assert::true($technique->Used, 'used');
                Assert::count(1, $calc->explanations, 'explanation');
            },

            // WHY: Calculate does not re-check availability. If Finesse is no longer lower,
            // there is no wound, but the technique is still marked used.
            'a finesse tie at calculate skips the wound and still spends the technique' => function () {
                [$world, , $technique, $foe] = $this->duel();
                $foe->ModifiedFinesse = 3;
                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::true($technique->Used, 'still used');
                Assert::count(0, $calc->explanations, 'no explanation');
            },
        ];
    }
}
