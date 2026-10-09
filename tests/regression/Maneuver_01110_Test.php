<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01110;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01110;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01110_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01110';
    }

    /** @return array{0:_01110,1:Maneuver_01110,2:GenericCharacter,3:GenericCharacter} */
    private function duel(TestWorld $world, int $actorCombat = 1): array
    {
        $risk = $world->placeCard(new _01110(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $actor->ModifiedCombat = $actorCombat;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;

        /** @var Maneuver_01110 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function resolve(TestWorld $world, Maneuver_01110 $maneuver): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'available when the adversary is alive' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'alive');
            },

            'unavailable when the adversary is in discard or locker' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->game->forceInDiscardOrLocker = true;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'dead');
            },

            'resolve wounds the adversary; Combat under 3 skips the choice transition' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world, 2);

                $this->resolve($world, $maneuver);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no choice');
            },

            // WHY: "If your participant has 3 Combat or more" — choice goes to the adversary's controller.
            'resolve with Combat 3+ wounds then offers 01110 choice to the adversary' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world, 3);

                $this->resolve($world, $maneuver);

                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wound first');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'choice');
                Assert::same('01110', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'adversary decides');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'maneuver');
            },

            // WHY: first wound may already have destroyed them — UI must not offer "Take Wound".
            'args canTakeWound is false when the adversary is in discard or locker' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 3);
                $world->game->forceInDiscardOrLocker = true;

                $args = $maneuver->getArgsFromManeuver(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01110,
                    'x'
                );

                Assert::false($args['canTakeWound'], 'destroyed already');
            },

            'args canTakeWound is true when the adversary is still in play' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 3);

                $args = $maneuver->getArgsFromManeuver(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01110,
                    'x'
                );

                Assert::true($args['canTakeWound'], 'alive');
            },

            'choice id 1 wounds the adversary again' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world, 3);

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01110,
                    'x',
                    1
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'second wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'choice id 1 refuses when the adversary is already destroyed' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 3);
                $world->game->forceInDiscardOrLocker = true;

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId(
                        $world->game,
                        States::DUEL_RESOLVE_MANEUVER_01110,
                        'x',
                        1
                    );
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
            },

            // WHY: use actor Location (duel site), not adversary Location — locker path fatals otherwise.
            'choice id 2 makes the actor\'s location uncontrolled' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world, 3);

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01110,
                    'x',
                    2
                );

                $uncontrolled = $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class);
                Assert::count(1, $uncontrolled, 'uncontrolled');
                Assert::same($actor->Location, $uncontrolled[0]->location, 'duel site via actor');
                Assert::same(1, $uncontrolled[0]->playerId, 'Risk controller');
            },

            'choice id 2 notifies when the location cannot become uncontrolled' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 3);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBecomeUncontrolled = false;

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01110,
                    'x',
                    2
                );

                Assert::count(0, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class), 'blocked');
                Assert::count(1, $world->game->notify->messages, 'notified');
            },
        ];
    }
}
