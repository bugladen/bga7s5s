<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01013;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_01013_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01013';
    }

    public function tests(): array
    {
        return [
            'available when actor wounds >= adversary wounds in duel' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $vissenta->Wounds = 2;
                $foe->Wounds = 2;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $vissenta;
                $world->theah->duelOpponent = $foe;

                /** @var Technique_01013 $technique */
                $technique = $vissenta->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'equal wounds ok');
            },

            'unavailable when fewer wounds than adversary' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $vissenta->Wounds = 1;
                $foe->Wounds = 3;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $vissenta;
                $world->theah->duelOpponent = $foe;

                /** @var Technique_01013 $technique */
                $technique = $vissenta->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'fewer wounds');
            },

            'resolve queues technique transition 01013' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_01013 $technique */
                $technique = $vissenta->getTechniques()[0];

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('01013', $transitions[0]->transition, 'transition');
            },

            'act id 1 chooses Thrust; id 0 chooses Parry' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_01013 $technique */
                $technique = $vissenta->getTechniques()[0];

                $technique->actFromTechniqueWithId(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_01013,
                    'duelChooseTechnique_01013',
                    1
                );
                Assert::true($technique->UseThrust, 'thrust chosen');

                $technique->actFromTechniqueWithId(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_01013,
                    'duelChooseTechnique_01013',
                    0
                );
                Assert::false($technique->UseThrust, 'parry chosen');
            },

            'calculate adds thrust or parry based on UseThrust' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_01013 $technique */
                $technique = $vissenta->getTechniques()[0];

                $technique->UseThrust = true;
                $thrustEvent = new EventDuelCalculateTechniqueValues();
                $thrustEvent->techniqueId = $technique->Id;
                $thrustEvent->theah = $world->theah;
                $technique->handleEvent($thrustEvent);
                Assert::same(1, $thrustEvent->thrust, 'thrust');
                Assert::same(0, $thrustEvent->parry, 'no parry');

                $technique->UseThrust = false;
                $parryEvent = new EventDuelCalculateTechniqueValues();
                $parryEvent->techniqueId = $technique->Id;
                $parryEvent->theah = $world->theah;
                $technique->handleEvent($parryEvent);
                Assert::same(1, $parryEvent->parry, 'parry');
                Assert::same(0, $parryEvent->thrust, 'no thrust');
            },

            'duel end resets UseThrust' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_01013 $technique */
                $technique = $vissenta->getTechniques()[0];
                $technique->UseThrust = true;

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->UseThrust, 'reset');
            },
        ];
    }
}
