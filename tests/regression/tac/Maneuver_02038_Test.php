<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02038;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_02038_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02038';
    }

    /**
     * @return array{0:_02038,1:Maneuver_02038,2:Maneuver_02038,3:GenericCharacter,4:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02038(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor', ['Pirate', 'Duelist']), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $actor->ModifiedCombat = 3;
        $actor->ModifiedFinesse = 3;
        $foe->ModifiedCombat = 1;
        $foe->ModifiedFinesse = 1;
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_02038 $pirate */
        $pirate = $risk->getManeuvers()[0];
        /** @var Maneuver_02038 $duelist */
        $duelist = $risk->getManeuvers()[1];
        return [$risk, $pirate, $duelist, $actor, $foe];
    }

    public function tests(): array
    {
        return [
            'state constant registered' => function () {
                Assert::same(52502038, States::DUEL_RESOLVE_MANEUVER_02038, 'state');
            },

            // Pirate gate is Combat strictly greater. Duelist gate is Finesse strictly greater.
            'Pirate maneuver is available when actor Combat is higher' => function () {
                $world = new TestWorld();
                [, $pirate, $duelist, $actor] = $this->scene($world);
                Assert::true($pirate->isAvailableToPlayer(1, $world->theah), 'pirate');
                Assert::true($duelist->isAvailableToPlayer(1, $world->theah), 'duelist');

                $actor->ModifiedTraits = ['Pirate'];
                Assert::true($pirate->isAvailableToPlayer(1, $world->theah), 'pirate only');
                Assert::false($duelist->isAvailableToPlayer(1, $world->theah), 'not duelist');
            },

            'unavailable when the stat is not strictly higher' => function () {
                $world = new TestWorld();
                [, $pirate, $duelist, $actor, $foe] = $this->scene($world);
                $actor->ModifiedCombat = 1;
                $foe->ModifiedCombat = 1;
                Assert::false($pirate->isAvailableToPlayer(1, $world->theah), 'combat tie');

                $actor->ModifiedCombat = 0;
                Assert::false($pirate->isAvailableToPlayer(1, $world->theah), 'combat lower');

                $actor->ModifiedFinesse = 1;
                $foe->ModifiedFinesse = 2;
                Assert::false($duelist->isAvailableToPlayer(1, $world->theah), 'finesse lower');
            },

            'unavailable without the required trait' => function () {
                $world = new TestWorld();
                [, $pirate, $duelist, $actor] = $this->scene($world);
                $actor->ModifiedTraits = ['Hero'];
                Assert::false($pirate->isAvailableToPlayer(1, $world->theah), 'not pirate');
                Assert::false($duelist->isAvailableToPlayer(1, $world->theah), 'not duelist');
            },

            'resolve offers the adversary the wound or draw choice' => function () {
                $world = new TestWorld();
                [$risk, $pirate, , , $foe] = $this->scene($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $pirate->Id;
                $event->theah = $world->theah;
                $pirate->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('02038', $transitions[0]->transition, 'name');
                Assert::same($foe->ControllerId, $transitions[0]->playerId, 'adversary');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($pirate->Id, $transitions[0]->internalId, 'maneuver');
            },

            'choosing the wound queues a wound on the adversary' => function () {
                $world = new TestWorld();
                [$risk, $pirate, , , $foe] = $this->scene($world);

                $pirate->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_02038,
                    'duelResolveManeuver_02038',
                    1
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same(1, $wounds[0]->wounds, 'one');
                Assert::same($pirate->Id, $wounds[0]->abilityId, 'ability');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'destroyed adversary cannot suffer a wound, so the owner draws' => function () {
                $world = new TestWorld();
                [$risk, $pirate, , , $foe] = $this->scene($world);
                $world->game->discardOrLockerByCardId[$foe->Id] = true;

                $event = new EventResolveManeuver();
                $event->maneuverId = $pirate->Id;
                $event->theah = $world->theah;
                $pirate->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no choice');
                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same($risk->ControllerId, $draws[0]->playerId, 'owner');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },

            'args hide the wound when the adversary is already destroyed' => function () {
                $world = new TestWorld();
                [, $pirate, , , $foe] = $this->scene($world);
                $world->game->discardOrLockerByCardId[$foe->Id] = true;

                $args = $pirate->getArgsFromManeuver(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_02038,
                    'duelResolveManeuver_02038'
                );

                Assert::false($args['canTakeWound'], 'destroyed');
            },

            'args offer the wound when the adversary is alive' => function () {
                $world = new TestWorld();
                [, $pirate] = $this->scene($world);

                $args = $pirate->getArgsFromManeuver(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_02038,
                    'duelResolveManeuver_02038'
                );

                Assert::true($args['canTakeWound'], 'alive');
            },

            'choosing the wound while destroyed draws instead' => function () {
                $world = new TestWorld();
                [$risk, $pirate, , , $foe] = $this->scene($world);
                $world->game->discardOrLockerByCardId[$foe->Id] = true;

                $pirate->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_02038,
                    'duelResolveManeuver_02038',
                    1
                );

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same($risk->ControllerId, $draws[0]->playerId, 'owner');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'choosing the draw lets the maneuver owner draw' => function () {
                $world = new TestWorld();
                [$risk, , $duelist] = $this->scene($world);

                $duelist->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_02038,
                    'duelResolveManeuver_02038',
                    2
                );

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same(1, $draws[0]->playerId, 'owner');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },

            'ignores resolve of the other maneuver instance' => function () {
                $world = new TestWorld();
                [, $pirate, $duelist] = $this->scene($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $duelist->Id;
                $event->theah = $world->theah;
                $pirate->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },
        ];
    }
}
