<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01133_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01133';
    }

    /** @return array{0:_01133,1:Maneuver_01133,2:GenericCharacter,3:GenericCharacter} */
    private function duel(TestWorld $world, array $actorTraits = ['Sorcerer']): array
    {
        $risk = $world->placeCard(new _01133(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor', $actorTraits), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01133 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    public function tests(): array
    {
        return [
            'is a Sorcerer Maneuver' => function () {
                Assert::instanceOf(ISorcererAbility::class, new Maneuver_01133(), 'ISorcererAbility');
            },

            'available when the duel actor is a Sorcerer' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'Sorcerer');
            },

            'unavailable when the actor is not a Sorcerer' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, []);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not Sorcerer');
            },

            'resolve queues transition 01133' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01133', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'internal');
            },

            'args list adjacent city locations excluding Home' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $args = $maneuver->getArgsFromManeuver(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01133,
                    'duelResolveManeuver_01133'
                );

                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'Forum only');
            },

            'choosing an adjacent location moves both participants and fires Sorcerer events' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);

                $maneuver->actFromManeuverWithIds(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01133,
                    'duelResolveManeuver_01133',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(2, $moves, 'both');
                Assert::same($actor->Id, $moves[0]->cardId, 'actor first');
                Assert::same($foe->Id, $moves[1]->cardId, 'foe second');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to Forum');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[1]->toLocation, 'foe to Forum');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'choosing a non-adjacent location throws' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithIds(
                        $world->game,
                        States::DUEL_RESOLVE_MANEUVER_01133,
                        'x',
                        [Game::LOCATION_CITY_BAZAAR]
                    );
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },
        ];
    }
}
