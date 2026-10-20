<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01033_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01033';
    }

    public function tests(): array
    {
        return [
            'available when actor Influence exceeds adversary and adversary not in locker' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $actor->ModifiedInfluence = 3;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->ModifiedInfluence = 1;
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01033 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Influence is not greater' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $actor->ModifiedInfluence = 1;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->ModifiedInfluence = 1;
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01033 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'tie');
            },

            'unavailable when adversary is in discard or locker' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $actor->ModifiedInfluence = 3;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->ModifiedInfluence = 1;
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $world->game->forceInDiscardOrLocker = true;

                /** @var Maneuver_01033 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'locker');
            },

            // WHY regression: journal 2026-07-19 Harpoon — check adversary at activate
            'eventCheck rejects Harpooned adversary' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Conditions[] = Game::HARPOON_CONDITION;
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);

                /** @var Maneuver_01033 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $event = new EventManeuverActivated();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $maneuver->eventCheck($event);
                } catch (UserException $e) {
                    $threw = true;
                    Assert::contains('Harpooned', $e->getMessage(), 'message');
                }
                Assert::true($threw, 'harpoon throws');
            },

            'eventCheck rejects Lodestone on adversary' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Conditions[] = Game::LODESTONE_CONDITION;
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01033 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $event = new EventManeuverActivated();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $maneuver->eventCheck($event);
                } catch (UserException $e) {
                    $threw = true;
                    Assert::contains('Lodestone', $e->getMessage(), 'message');
                }
                Assert::true($threw, 'lodestone throws');
            },

            'resolve moves adversary Home' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                /** @var Maneuver_01033 $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($foe->Id, $moves[0]->cardId, 'adversary');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'home');
            },
        ];
    }
}
