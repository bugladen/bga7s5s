<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01059;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01059_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01059';
    }

    /** @return array{0:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk,1:Maneuver_01059,2:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Character,3:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Character} */
    private function scenario(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01059(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_01059 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function select(TestWorld $world, Maneuver_01059 $maneuver, string $location): void
    {
        $maneuver->actFromManeuverWithIds(
            $world->game,
            States::DUEL_RESOLVE_MANEUVER_01059,
            'duelResolveManeuver_01059',
            [$location]
        );
    }

    private function endOfRound(TestWorld $world, Maneuver_01059 $maneuver): void
    {
        $end = new EventDuelEndOfRound();
        $end->theah = $world->theah;
        $maneuver->handleEvent($end);
    }

    private function activate(TestWorld $world, Maneuver_01059 $maneuver, \Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk $risk): void
    {
        $activated = new EventManeuverActivated();
        $activated->playerId = 1;
        $activated->ownerId = $risk->Id;
        $activated->maneuverId = $maneuver->Id;
        $activated->theah = $world->theah;
        $maneuver->eventCheck($activated);
    }

    public function tests(): array
    {
        return [
            'resolve queues transition 01059' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->scenario($world);

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $resolve->playerId = 1;
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01059', $transitions[0]->transition, 'transition');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'internal id');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'args list adjacent city locations for actor' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->scenario($world);

                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_RESOLVE_MANEUVER_01059, 'duelResolveManeuver_01059');
                Assert::same($actor->Id, $args['performerId'], 'performerId');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'Forum only');
            },

            // WHY: selection is stored on the maneuver and only applied at EndOfRound, so nothing moves yet.
            'selecting a location stores it without moving immediately' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scenario($world);

                $this->select($world, $maneuver, Game::LOCATION_CITY_FORUM);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move yet');
                Assert::count(1, $world->game->gamestate->transitions, 'nextState');
            },

            'non-adjacent selection is rejected' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scenario($world);

                $threw = false;
                try {
                    $this->select($world, $maneuver, Game::LOCATION_CITY_BAZAAR);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not adjacent');
                $this->endOfRound($world, $maneuver);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no stored location');
            },

            'EventDuelEndOfRound moves actor to stored location then clears it' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->scenario($world);

                $this->select($world, $maneuver, Game::LOCATION_CITY_FORUM);
                $this->endOfRound($world, $maneuver);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($actor->Id, $moves[0]->cardId, 'actor');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');

                $world->theah->takeQueuedEvents();
                $this->endOfRound($world, $maneuver);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'selection cleared');
            },

            'EndOfRound without a selection does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scenario($world);
                $this->endOfRound($world, $maneuver);
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'EndOfRound skips move when actor is in discard or locker' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scenario($world);
                $this->select($world, $maneuver, Game::LOCATION_CITY_FORUM);
                $world->game->forceInDiscardOrLocker = true;

                $this->endOfRound($world, $maneuver);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },

            'ManeuverCanceled clears stored location' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scenario($world);
                $this->select($world, $maneuver, Game::LOCATION_CITY_FORUM);

                $canceled = new EventManeuverCanceled();
                $canceled->maneuverId = $maneuver->Id;
                $canceled->playerId = 1;
                $canceled->theah = $world->theah;
                $maneuver->handleEvent($canceled);

                $this->endOfRound($world, $maneuver);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'canceled');
            },

            'eventCheck allows activation with no movement condition' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->scenario($world);
                $this->activate($world, $maneuver, $risk);
                Assert::true(true, 'no throw');
            },

            'eventCheck blocks Harpooned actor during a duel' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->scenario($world);
                $actor->addCondition(Game::HARPOON_CONDITION);

                $threw = false;
                try {
                    $this->activate($world, $maneuver, $risk);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'harpooned');
            },

            // WHY: Harpoon only restricts movement "for the remainder of the duel"; outside a duel the gate is off.
            'eventCheck ignores Harpoon when not in a duel' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->scenario($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $actor->addCondition(Game::HARPOON_CONDITION);

                $this->activate($world, $maneuver, $risk);
                Assert::true(true, 'no throw');
            },

            'eventCheck blocks Shackled actor' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->scenario($world);
                $actor->addCondition(Game::SHACKLES_CONDITION);

                $threw = false;
                try {
                    $this->activate($world, $maneuver, $risk);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'shackled');
            },

            'eventCheck ignores activation of other maneuvers' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->scenario($world);
                $actor->addCondition(Game::SHACKLES_CONDITION);

                $activated = new EventManeuverActivated();
                $activated->playerId = 1;
                $activated->ownerId = $risk->Id;
                $activated->maneuverId = 'other';
                $activated->theah = $world->theah;
                $maneuver->eventCheck($activated);
                Assert::true(true, 'no throw');
            },
        ];
    }
}
