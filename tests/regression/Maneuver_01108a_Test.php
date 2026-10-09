<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01107;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01108;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01108a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01108a_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01108a';
    }

    /** @return array{0:_01108,1:Maneuver_01108a,2:GenericCharacter,3:GenericCharacter} */
    private function duel(TestWorld $world, array $actorTraits = ['Scoundrel'], bool $foeHasHand = true): array
    {
        $risk = $world->placeCard(new _01108(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor', $actorTraits), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        if ($foeHasHand) {
            $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);
        }

        /** @var Maneuver_01108a $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    public function tests(): array
    {
        return [
            'available when the actor is a Scoundrel and the adversary has a hand card' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'Scoundrel + hand');
            },

            'unavailable when the actor is not a Scoundrel' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, ['Pirate']);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not Scoundrel');
            },

            'unavailable when the adversary has an empty hand' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, ['Scoundrel'], false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'empty hand');
            },

            'resolve hands discard choice to the adversary\'s controller' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01108', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'adversary chooses');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'maneuver');
            },

            'act discards the chosen hand card as an effect' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);
                $handCard = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);
                $world->game->activePlayerId = 2;

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01108,
                    'x',
                    $handCard->Id
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discarded');
                Assert::same($handCard->Id, $discards[0]->cardId, 'chosen card');
                Assert::same($risk->Id, $discards[0]->sourceId, 'source Risk');
                Assert::true($discards[0]->asEffect, 'as effect');
                Assert::false($discards[0]->AsPlayed, 'not played');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'act refuses a card the active player does not control' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $mine = $world->placeCard(new _01107(), Game::LOCATION_HAND, 1);
                $world->game->activePlayerId = 2;

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId(
                        $world->game,
                        States::DUEL_RESOLVE_MANEUVER_01108,
                        'x',
                        $mine->Id
                    );
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
