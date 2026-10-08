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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01051;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01051_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01051';
    }

    /** @return array{0:_01051,1:Maneuver_01051,2:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Character} */
    private function duelWithRisk(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01051(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);

        /** @var Maneuver_01051 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor];
    }

    private function actWith(TestWorld $world, Maneuver_01051 $maneuver, int $id): void
    {
        $maneuver->actFromManeuverWithId(
            $world->game,
            States::DUEL_RESOLVE_MANEUVER_01051,
            'duelResolveManeuver_01051',
            $id
        );
    }

    public function tests(): array
    {
        return [
            'available with friendly Mercenary at actor location' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);

                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: Uwe is not a printed Mercenary; Character::hasTrait queryCard override makes him
            // count only when queried by 01051 (owner passed in Maneuver_01051).
            'available with Uwe Zimmerman via queryCard' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $world->placeCharacter(new _01043(), Game::LOCATION_CITY_DOCKS, 1);

                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'Uwe counts');
            },

            'unavailable without a Mercenary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $world->placeCharacter(new GenericCharacter('Pal'), Game::LOCATION_CITY_DOCKS, 1);

                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'no merc');
            },

            // WHY: "your target Mercenary" — the participant cannot soak wounds onto itself.
            'unavailable when only Mercenary is the actor itself' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01051(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('MercActor', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);

                /** @var Maneuver_01051 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'self excluded');
            },

            'unavailable when Mercenary is an opposing character' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $world->placeCharacter(new GenericCharacter('EnemyMerc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);

                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'opposing merc');
            },

            'unavailable when Mercenary is at a different location' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_FORUM, 1);

                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'other location');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::IN_DUEL, false);

                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'resolve queues transition 01051' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duelWithRisk($world);

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $resolve->playerId = 1;
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01051', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'getArgs lists friendly Mercenaries excluding actor' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duelWithRisk($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Pal'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('EnemyMerc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);

                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_RESOLVE_MANEUVER_01051, 'x');
                Assert::same([$merc->Id], $args['characterIds'], 'only friendly merc');
                Assert::false(in_array($actor->Id, $args['characterIds'], true), 'actor excluded');
            },

            'act picks Mercenary and records remap' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duelWithRisk($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);

                $this->actWith($world, $maneuver, $merc->Id);

                Assert::same($actor->Id, $maneuver->characterToPreventWoundsFrom, 'from actor');
                Assert::same($merc->Id, $maneuver->CharacterCurrentlyTakingWounds, 'to merc');
                Assert::same([null], $world->game->gamestate->transitions, 'next state');
            },

            'act picks Uwe via queryCard' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $uwe = $world->placeCharacter(new _01043(), Game::LOCATION_CITY_DOCKS, 1);

                $this->actWith($world, $maneuver, $uwe->Id);

                Assert::same($uwe->Id, $maneuver->CharacterCurrentlyTakingWounds, 'Uwe takes wounds');
            },

            'act rejects non-Mercenary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $pal = $world->placeCharacter(new GenericCharacter('Pal'), Game::LOCATION_CITY_DOCKS, 1);

                $threw = false;
                try {
                    $this->actWith($world, $maneuver, $pal->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::same(0, $maneuver->CharacterCurrentlyTakingWounds, 'not recorded');
            },

            'act rejects opposing Mercenary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $enemy = $world->placeCharacter(new GenericCharacter('EnemyMerc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);

                $threw = false;
                try {
                    $this->actWith($world, $maneuver, $enemy->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },

            'eventCheck remaps EventCharacterBeingWounded from actor to chosen Mercenary' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duelWithRisk($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $this->actWith($world, $maneuver, $merc->Id);

                $event = new EventCharacterBeingWounded();
                $event->characterId = $actor->Id;
                $event->wounds = 1;
                $event->theah = $world->theah;
                $maneuver->eventCheck($event);

                Assert::same($merc->Id, $event->characterId, 'remapped');
            },

            'eventCheck remaps EventCharacterWounded from actor to chosen Mercenary' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duelWithRisk($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $this->actWith($world, $maneuver, $merc->Id);

                $event = new EventCharacterWounded();
                $event->characterId = $actor->Id;
                $event->wounds = 1;
                $event->theah = $world->theah;
                $maneuver->eventCheck($event);

                Assert::same($merc->Id, $event->characterId, 'remapped');
            },

            'eventCheck leaves other characters untouched' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_DOCKS, 2);
                $this->actWith($world, $maneuver, $merc->Id);

                $event = new EventCharacterBeingWounded();
                $event->characterId = $foe->Id;
                $event->theah = $world->theah;
                $maneuver->eventCheck($event);

                Assert::same($foe->Id, $event->characterId, 'unchanged');
            },

            // WHY: before act, characterToPreventWoundsFrom is 0 — a wound event with characterId 0
            // must not be rewritten to 0 either (and real actor wounds stay put).
            'eventCheck is inert before a Mercenary is chosen' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duelWithRisk($world);

                $event = new EventCharacterBeingWounded();
                $event->characterId = $actor->Id;
                $event->theah = $world->theah;
                $maneuver->eventCheck($event);

                Assert::same($actor->Id, $event->characterId, 'actor still wounded');
            },

            // WHY: "Until your next round" — new round for the same actor ends the substitution.
            'new round for the same actor clears remap' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duelWithRisk($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $this->actWith($world, $maneuver, $merc->Id);

                $round = new EventDuelNewRound();
                $round->actorId = $actor->Id;
                $round->theah = $world->theah;
                $maneuver->handleEvent($round);

                Assert::same(0, $maneuver->characterToPreventWoundsFrom, 'cleared from');
                Assert::same(0, $maneuver->CharacterCurrentlyTakingWounds, 'cleared to');
            },

            'new round for the opposing actor keeps remap' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duelWithRisk($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $this->actWith($world, $maneuver, $merc->Id);

                $round = new EventDuelNewRound();
                $round->actorId = $actor->Id + 1;
                $round->theah = $world->theah;
                $maneuver->handleEvent($round);

                Assert::same($actor->Id, $maneuver->characterToPreventWoundsFrom, 'kept');
                Assert::same($merc->Id, $maneuver->CharacterCurrentlyTakingWounds, 'kept');
            },

            'duel end clears remap' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $this->actWith($world, $maneuver, $merc->Id);

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $maneuver->handleEvent($end);

                Assert::same(0, $maneuver->characterToPreventWoundsFrom, 'cleared from');
                Assert::same(0, $maneuver->CharacterCurrentlyTakingWounds, 'cleared to');
            },

            'maneuver canceled clears remap only for this maneuver id' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duelWithRisk($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $this->actWith($world, $maneuver, $merc->Id);

                $other = new EventManeuverCanceled();
                $other->maneuverId = 'someOtherManeuver';
                $other->theah = $world->theah;
                $maneuver->handleEvent($other);
                Assert::same($merc->Id, $maneuver->CharacterCurrentlyTakingWounds, 'other id ignored');

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);
                Assert::same(0, $maneuver->characterToPreventWoundsFrom, 'cleared from');
                Assert::same(0, $maneuver->CharacterCurrentlyTakingWounds, 'cleared to');
            },
        ];
    }
}
