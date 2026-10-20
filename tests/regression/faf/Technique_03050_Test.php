<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03050;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_03050_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03050';
    }

    public function tests(): array
    {
        return [
            // WHY: His own combat card must be Flourish or Sorcery. An opponent's
            // Flourish does not count. Calculate adds 1 Riposte and spends the technique.
            'adds 1 Riposte when his combat card is Flourish or Sorcery' => function () {
                $world = new TestWorld();
                $daichi = $world->placeCharacter(new _03050(), Game::LOCATION_CITY_DOCKS, 1);
                $flourish = $world->placeCard(new _03036(), Game::LOCATION_DUELING_LINE, 1);
                $world->theah->duelActor = $daichi;
                $world->theah->duelOpponent = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->combatCardsForCurrentRound = [$flourish];
                $world->game->globals->set(Game::IN_DUEL, true);
                /** @var Technique_03050 $technique */
                $technique = $daichi->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'flourish');

                $flourish->ControllerId = 2;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'opponent\'s card');
                $sorcery = $world->placeCard(new _03048(), Game::LOCATION_DUELING_LINE, 1);
                $sorcery->removeTrait($world->game, 'Flourish');
                $sorcery->addTrait($world->game, 'Sorcery', true);
                $world->theah->combatCardsForCurrentRound = [$sorcery];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'sorcery');

                $plain = $world->placeCard(new _03048(), Game::LOCATION_DUELING_LINE, 1);
                $plain->addTrait($world->game, 'Hero', true);
                $plain->removeTrait($world->game, 'Flourish');
                $plain->removeTrait($world->game, 'Virtue');
                $world->theah->combatCardsForCurrentRound = [$plain];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'neither trait');

                $world->theah->combatCardsForCurrentRound = [$flourish];
                $flourish->ControllerId = 1;
                $world->theah->duelActor = $world->theah->duelOpponent;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not the actor');
                $world->theah->duelActor = $daichi;

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->theah = $world->theah;
                $calc->techniqueId = $technique->Id;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->riposte, 'one riposte');
                Assert::true($technique->Used, 'spent');
            },
        ];
    }
}
