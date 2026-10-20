<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01057;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class Maneuver_01057_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01057';
    }

    public function tests(): array
    {
        return [
            // WHY: createGainLethalEvent keys off who is challenger vs defender in the duel.
            // Actor == challenger → challenger lethal flag stays null, defender flag set true.
            'resolve as challenger queues GainLethal and RangedAbilityPlayed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01057(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01057 $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $resolve->playerId = 1;
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);

                $threat = $world->theah->queuedOfType(EventThreatModified::class);
                Assert::count(1, $threat, 'lethal event');
                Assert::same(null, $threat[0]->challengerThreatIsLethal, 'challenger flag untouched');
                Assert::same(true, $threat[0]->defenderThreatIsLethal, 'defender flag lethal');

                $ranged = $world->theah->queuedOfType(EventRangedAbilityPlayed::class);
                Assert::count(1, $ranged, 'ranged played');
                Assert::same(1, $ranged[0]->playerId, 'controller');
                Assert::same($risk->Id, $ranged[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $ranged[0]->abilityId, 'ability');
                Assert::same($actor->Id, $ranged[0]->performerId, 'performer');
            },

            'ignores resolve for a different maneuver id' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01057(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01057 $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = 'someOtherManeuver';
                $resolve->playerId = 1;
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);

                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
