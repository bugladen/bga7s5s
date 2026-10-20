<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04038;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_04038_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04038';
    }

    public function tests(): array
    {
        return [
            // WHY: Academic only. There is no printed cost, so an already en garde and
            // unwounded actor can still play it. Resolve en gardes only if engaged and
            // heals only if wounded.
            'en gardes and heals the academic actor' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04038(), Game::LOCATION_DUELING_LINE, 1);
                $academic = $world->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $academic->Engaged = true;
                $academic->Wounds = 2;
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Maneuver_04038 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $world->theah->duelActor = $hero;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not an academic');
                $world->theah->duelActor = $academic;
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'academic');

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);
                Assert::same($academic->Id, $world->theah->queuedOfType(EventCardEngarded::class)[0]->cardId, 'en garde');
                Assert::same(1, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->wounds, 'one wound');

                $academic->Engaged = false;
                $academic->Wounds = 0;
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'still playable');
                $world->theah->takeQueuedEvents();
                $maneuver->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'already en garde');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'no wound');
            },
        ];
    }
}
