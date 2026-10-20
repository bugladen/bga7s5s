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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01115;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01115;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01115_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01115';
    }

    /** @return array{0:_01115,1:Maneuver_01115,2:Character,3:Character} */
    private function duel(TestWorld $world, int $actorFinesse = 3, int $foeFinesse = 1): array
    {
        $risk = $world->placeCard(new _01115(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $actor->ModifiedFinesse = $actorFinesse;
        $foe->ModifiedFinesse = $foeFinesse;
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01115 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    public function tests(): array
    {
        return [
            'available when actor Finesse strictly exceeds the adversary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 3, 1);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'higher');
            },

            'unavailable when Finesse is equal' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 2, 2);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'tie');
            },

            'unavailable when Finesse is lower' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 1, 3);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'lower');
            },

            'resolve hands the discard choice to the adversary\'s controller' => function () {
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
                Assert::same('01115', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'adversary controller chooses');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'internal');
            },

            'adversary discard queues CardDiscardedFromHand as an effect' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);
                $hand = $world->placeCard(new _01115(), Game::LOCATION_HAND, 2);
                $world->game->activePlayerId = 2;

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01115, 'x', $hand->Id);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($hand->Id, $discards[0]->cardId, 'card');
                Assert::same($risk->Id, $discards[0]->sourceId, 'source');
                Assert::true($discards[0]->asEffect, 'as effect');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'refuses a card the active player does not control' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $theirs = $world->placeCard(new _01115(), Game::LOCATION_HAND, 1);
                $world->game->activePlayerId = 2;

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01115, 'x', $theirs->Id);
                } catch (\BgaUserException | UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
            },
        ];
    }
}
