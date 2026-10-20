<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04040;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_04040_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04040';
    }

    public function tests(): array
    {
        return [
            // WHY: Academic maneuver. The adversary must still be in the duel, not the
            // locker. Resolve wounds that adversary.
            'wounds the adversary when the actor is an academic' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04040(), 'Dueling Line', 1);
                $academic = $world->placeCharacter(new _04033(), 'City Docks', 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), 'City Docks', 2);
                /** @var Maneuver_04040 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $world->theah->duelActor = $academic;
                $world->theah->duelOpponent = $foe;
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'academic');

                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), 'City Docks', 1);
                $world->theah->duelActor = $hero;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not an academic');

                $world->theah->duelActor = $academic;
                $world->game->discardOrLockerByCardId[$foe->Id] = true;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'adversary in the locker');
                $world->game->discardOrLockerByCardId[$foe->Id] = false;

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);
                Assert::same($foe->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'adversary');
                Assert::same(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->wounds, 'one');
            },
        ];
    }
}
