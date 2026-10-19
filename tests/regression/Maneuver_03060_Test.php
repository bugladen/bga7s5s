<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03060;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_03060_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03060';
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling plus a Sorcerer you control at the duel location. The actor's
            // own wounds are not a gate. Resolve heals two only when the actor is wounded.
            'heals two wounds from the participant when you have a sorcerer there' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03060(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 2);
                $maneuver = $risk->getManeuvers()[0];
                $world->theah->duelActor = $actor;
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'opponent sorcerer');

                $world->placeCharacter(new GenericCharacter('Mine', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'your sorcerer');

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'unwounded');

                $actor->Wounds = 1;
                $maneuver->handleEvent($resolve);
                Assert::same(2, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->wounds, 'two');
                Assert::same($actor->Id, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->characterId, 'actor');
            },
        ];
    }
}
