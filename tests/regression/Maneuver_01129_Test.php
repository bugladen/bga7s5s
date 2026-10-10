<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01129;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01129;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;

/**
 * WHY (journal 2026-10-06-04): rest-of-duel ban lives in Game::BORETS_MANEUVER_TECHNIQUE_LOCK
 * + Theah::eventCheck, not instance eventCheck — Miyato locker omits the Risk from buildCity.
 */
class Maneuver_01129_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01129';
    }

    /** @return array{0:_01129,1:Maneuver_01129} */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01129(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01129 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver];
    }

    private function resolve(TestWorld $world, Maneuver_01129 $maneuver): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'resolve arms IsActive and the global Borets lock' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);

                $this->resolve($world, $maneuver);

                Assert::true($maneuver->IsActive, 'active');
                $lock = Maneuver_01129::getLock($world->game);
                Assert::true(is_array($lock), 'lock armed');
                Assert::same($risk->getInjectCode(), $lock['sourceInjectCode'], 'source');
                Assert::same($maneuver->Id, $lock['maneuverId'], 'maneuver id');
            },

            'resolve for another maneuver id is ignored' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'other';
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::false($maneuver->IsActive, 'not armed');
                Assert::same(null, Maneuver_01129::getLock($world->game), 'no lock');
            },

            'Theah eventCheck blocks ManeuverActivated while locked' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $this->resolve($world, $maneuver);

                $activated = new EventManeuverActivated();
                $activated->theah = $world->theah;
                $threw = false;
                try {
                    $world->theah->eventCheck($activated);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'maneuver blocked');
            },

            'Theah eventCheck blocks TechniqueActivated while locked' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $this->resolve($world, $maneuver);

                $activated = new EventTechniqueActivated();
                $activated->theah = $world->theah;
                $threw = false;
                try {
                    $world->theah->eventCheck($activated);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'technique blocked');
            },

            'eventCheck allows activations when unlocked' => function () {
                $world = new TestWorld();
                $this->duel($world);

                $activated = new EventManeuverActivated();
                $activated->theah = $world->theah;
                $world->theah->eventCheck($activated);
                Assert::true(true, 'no throw');
            },

            'ManeuverCanceled for this id clears the lock and IsActive' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $this->resolve($world, $maneuver);

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);

                Assert::false($maneuver->IsActive, 'cleared');
                Assert::same(null, Maneuver_01129::getLock($world->game), 'lock gone');
            },

            'ManeuverCanceled for another id leaves the lock armed' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $this->resolve($world, $maneuver);

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = 'other';
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);

                Assert::true($maneuver->IsActive, 'still active');
                Assert::true(is_array(Maneuver_01129::getLock($world->game)), 'lock kept');
            },

            'EventDuelEnd clears the lock even if the Risk left play' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);
                $this->resolve($world, $maneuver);
                // Simulate Miyato locker: Risk gone from world; global must still clear.
                $risk->Location = Game::LOCATION_CITY_LOCKER;

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $maneuver->handleEvent($end);

                Assert::false($maneuver->IsActive, 'cleared');
                Assert::same(null, Maneuver_01129::getLock($world->game), 'lock gone');
            },

            'static clearLock / clearLockForManeuver are id-aware' => function () {
                $world = new TestWorld();
                Maneuver_01129::armLock($world->game, 'src', 'm1');
                Maneuver_01129::clearLockForManeuver($world->game, 'm2');
                Assert::true(is_array(Maneuver_01129::getLock($world->game)), 'wrong id keeps');
                Maneuver_01129::clearLockForManeuver($world->game, 'm1');
                Assert::same(null, Maneuver_01129::getLock($world->game), 'matching clears');
                Maneuver_01129::armLock($world->game, 'src', 'm1');
                Maneuver_01129::clearLock($world->game);
                Assert::same(null, Maneuver_01129::getLock($world->game), 'clear all');
            },
        ];
    }
}
