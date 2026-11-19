<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Corncob;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Corncob;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_05Corncob_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_05Corncob';
    }

    public function tests(): array
    {
        return [
            // WHY: Covert Technique — duel + actor + uncontrolled City (Valeri / En Garde Technique shape).
            'is only offered in a duel at an uncontrolled city when Cobb is the actor' => function () {
                $world = new TestWorld();
                $corncob = $world->placeCharacter(new _05Corncob(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Technique_05Corncob $technique */
                $technique = $corncob->getTechniques()[0];

                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $corncob;

                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'baseline');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'controlled');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $corncob->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'Home');
                $corncob->Location = Game::LOCATION_CITY_DOCKS;

                $world->theah->duelActor = $foe;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not actor');
                $world->theah->duelActor = $corncob;

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'adds +1 Parry on Calculate' => function () {
                $world = new TestWorld();
                $corncob = $world->placeCharacter(new _05Corncob(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_05Corncob $technique */
                $technique = $corncob->getTechniques()[0];

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->parry = 0;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->parry, '+1 Parry');
            },
        ];
    }
}
