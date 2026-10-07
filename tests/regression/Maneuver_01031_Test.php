<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01031;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01031_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01031';
    }

    public function tests(): array
    {
        return [
            'available when own Red Hand shares actor location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01031(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->theah->duelActor = $actor;

                /** @var Maneuver_01031 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without Red Hand at actor location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01031(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $actor;

                /** @var Maneuver_01031 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'no RH');
            },

            'resolve activates and calculate adds thrust per Red Hand' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01031(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('RH1', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(
                    new GenericCharacter('RH2', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->theah->duelActor = $actor;

                /** @var Maneuver_01031 $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);
                Assert::true($maneuver->IsActive, 'active');
                Assert::same(Game::LOCATION_CITY_DOCKS, $maneuver->DuelLocation, 'location');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->thrust = 0;
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->thrust, '+1 per RH');
            },

            'end of round offers destroy Thug when active' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01031(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->theah->duelActor = $actor;

                /** @var Maneuver_01031 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $maneuver->IsActive = true;
                $maneuver->DuelLocation = Game::LOCATION_CITY_DOCKS;

                $end = new EventDuelEndOfRound();
                $end->theah = $world->theah;
                $maneuver->handleEvent($end);

                Assert::false($maneuver->IsActive, 'cleared');
                Assert::same('01031', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offer');
            },

            'destroying Thug queues destroy and Gain Lethal' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01031(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01031 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $maneuver->DuelLocation = Game::LOCATION_CITY_DOCKS;

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_END_OF_ROUND_01031,
                    'duelEndOfRound_01031',
                    $thug->Id
                );

                Assert::same($thug->Id, $world->theah->queuedOfType(EventCharacterDestroyed::class)[0]->characterId, 'destroy');
                Assert::count(1, $world->theah->queuedOfType(EventThreatModified::class), 'lethal');
            },
        ];
    }
}
