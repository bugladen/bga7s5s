<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01164;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01164;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Maneuver_01164_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01164';
    }

    /** @return array{0:_01164,1:Maneuver_01164,2:GenericCharacter,3:GenericCharacter} */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01164(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_01164 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function moveCharacter(Maneuver_01164 $maneuver): int
    {
        $prop = new ReflectionProperty(Maneuver_01164::class, 'MoveCharacter');
        $prop->setAccessible(true);
        return (int)$prop->getValue($maneuver);
    }

    private function moveLocation(Maneuver_01164 $maneuver): string
    {
        $prop = new ReflectionProperty(Maneuver_01164::class, 'MoveLocation');
        $prop->setAccessible(true);
        return (string)$prop->getValue($maneuver);
    }

    public function tests(): array
    {
        return [
            'resolve queues transition 01164' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->adversaryId = $foe->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01164', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'args list adjacent city locations for the actor' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $args = $maneuver->getArgsFromManeuver(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01164,
                    'duelResolveManeuver_01164'
                );

                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], '2p Docks→Forum');
            },

            // WHY: Move is deferred to EndOfRound — act only records character/location.
            'act records deferred move and does not queue CardMoving yet' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);

                $maneuver->actFromManeuverWithIds(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01164,
                    'duelResolveManeuver_01164',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::same($actor->Id, $this->moveCharacter($maneuver), 'character');
                Assert::same(Game::LOCATION_CITY_FORUM, $this->moveLocation($maneuver), 'location');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'deferred');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'act refuses a non-adjacent location' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithIds(
                        $world->game,
                        States::DUEL_RESOLVE_MANEUVER_01164,
                        'x',
                        [Game::LOCATION_CITY_BAZAAR]
                    );
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Bazaar refused');
                Assert::same(0, $this->moveCharacter($maneuver), 'not armed');
            },

            'end of round queues the deferred move then clears the arm' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->duel($world);
                $maneuver->actFromManeuverWithIds(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01164,
                    'x',
                    [Game::LOCATION_CITY_FORUM]
                );
                $world->game->gamestate->transitions = [];
                $risk->IsUpdated = false;

                $eor = new EventDuelEndOfRound();
                $eor->theah = $world->theah;
                $maneuver->handleEvent($eor);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($actor->Id, $moves[0]->cardId, 'actor');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'Forum');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::same(0, $this->moveCharacter($maneuver), 'cleared');
                Assert::same('', $this->moveLocation($maneuver), 'location cleared');
                // WHY: Deferred state is on the Maneuver serialized with the Risk — EOR must dirty owner.
                Assert::true($risk->IsUpdated, 'owner dirty');
            },

            'end of round skips move when the participant is discarded/locker' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $maneuver->actFromManeuverWithIds(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01164,
                    'x',
                    [Game::LOCATION_CITY_FORUM]
                );
                $world->game->discardOrLockerByCardId[$actor->Id] = true;

                $eor = new EventDuelEndOfRound();
                $eor->theah = $world->theah;
                $maneuver->handleEvent($eor);

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
                Assert::same(0, $this->moveCharacter($maneuver), 'still cleared');
            },

            'maneuver cancel clears the deferred move' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $maneuver->actFromManeuverWithIds(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01164,
                    'x',
                    [Game::LOCATION_CITY_FORUM]
                );

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);

                Assert::same(0, $this->moveCharacter($maneuver), 'cleared');
                Assert::same('', $this->moveLocation($maneuver), 'location cleared');
            },

            // WHY (production comment): keep button visible under Harpooned — fail at activate
            // so the player sees why, rather than locking a location that fails at EndOfRound.
            'eventCheck blocks activate when the actor is Harpooned' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->addCondition(Game::HARPOON_CONDITION);

                $event = new EventManeuverActivated();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $maneuver->eventCheck($event);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Harpooned');
            },

            'eventCheck blocks activate when the actor is Shackled' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->addCondition(Game::SHACKLES_CONDITION);

                $event = new EventManeuverActivated();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $maneuver->eventCheck($event);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Shackled');
            },

            'eventCheck allows activate when the actor can move' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $event = new EventManeuverActivated();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;
                $maneuver->eventCheck($event);
                Assert::true(true, 'allowed');
            },

            'state constant registered' => function () {
                Assert::same(52501164, States::DUEL_RESOLVE_MANEUVER_01164, 'maneuver state');
            },
        ];
    }
}
