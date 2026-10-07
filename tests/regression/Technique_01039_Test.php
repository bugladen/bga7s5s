<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01039;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_01039_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01039';
    }

    public function tests(): array
    {
        return [
            'available with Mercenary ally and engaged adversary in duel' => function () {
                $world = new TestWorld();
                $philip = $world->placeCharacter(new _01039(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $philip;
                $world->theah->duelOpponent = $foe;

                /** @var Technique_01039 $technique */
                $technique = $philip->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: Uwe counts as Mercenary when Philip is queryCard
            'available when Uwe counts as Mercenary for Philip' => function () {
                $world = new TestWorld();
                $philip = $world->placeCharacter(new _01039(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new _01043(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $philip;
                $world->theah->duelOpponent = $foe;

                /** @var Technique_01039 $technique */
                $technique = $philip->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'Uwe');
            },

            'unavailable when adversary not engaged' => function () {
                $world = new TestWorld();
                $philip = $world->placeCharacter(new _01039(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $philip;
                $world->theah->duelOpponent = $foe;

                /** @var Technique_01039 $technique */
                $technique = $philip->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'en garde foe');
            },

            'resolve wounds engaged adversary when Merc present' => function () {
                $world = new TestWorld();
                $philip = $world->placeCharacter(new _01039(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;
                $world->theah->duelOpponent = $foe;

                /** @var Technique_01039 $technique */
                $technique = $philip->getTechniques()[0];
                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
            },
        ];
    }
}
