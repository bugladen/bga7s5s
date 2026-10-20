<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01113;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01113;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01113_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01113';
    }

    /** @return array{0:_01113,1:Maneuver_01113,2:Character,3:Character} */
    private function duel(TestWorld $world, bool $pirate = true): array
    {
        $risk = $world->placeCard(new _01113(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(
            new GenericCharacter('Actor', $pirate ? ['Pirate'] : []),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01113 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function equip(TestWorld $world, Attachment $attachment, Character $host): Attachment
    {
        $placed = $world->placeCard($attachment, $host->Location, $host->ControllerId);
        $placed->AttachedToId = $host->Id;
        $host->Attachments[] = $placed->Id;
        return $placed;
    }

    private function resolve(TestWorld $world, Maneuver_01113 $maneuver, Character $foe): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->adversaryId = $foe->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'targets cards' => function () {
                Assert::instanceOf(IAbilityThatTargetsCards::class, new Maneuver_01113(), 'cards');
            },

            'available when Pirate actor faces an equipped adversary' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $this->equip($world, new _01049(), $foe);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'equipped');
            },

            'available when the attachment is only in the adversary discard' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->placeCard(new _01049(), $world->game->getPlayerDiscardDeckName(2), 2);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'discard');
            },

            'unavailable when the actor is not a Pirate' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world, false);
                $this->equip($world, new _01049(), $foe);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not Pirate');
            },

            'unavailable with nothing to steal' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'bare');
            },

            'resolve queues transition 01113' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $this->equip($world, new _01049(), $foe);

                $this->resolve($world, $maneuver, $foe);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01113', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'internal');
            },

            'args list equipped and discard attachments' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $equipped = $this->equip($world, new _01049(), $foe);
                $discarded = $world->placeCard(new _01049(), $world->game->getPlayerDiscardDeckName(2), 2);

                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_RESOLVE_MANEUVER_01113, 'x');

                $ids = array_column($args['attachments'], 'id');
                Assert::true(in_array($equipped->Id, $ids, true), 'equipped listed');
                Assert::true(in_array($discarded->Id, $ids, true), 'discard listed');
            },

            'choosing an equipped attachment unequips then enters pay' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->duel($world);
                $weapon = $this->equip($world, new _01049(), $foe);

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01113, 'x', $weapon->Id);

                Assert::same(1, $weapon->ControllerId, 'controller flipped');
                Assert::same($actor->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'actor as performer');
                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'unequip');
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'discard from play');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToHand::class), 'to hand');
                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay');
                Assert::same('01113_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pay step');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'choosing a discard attachment skips unequip' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $discarded = $world->placeCard(new _01049(), $world->game->getPlayerDiscardDeckName(2), 2);

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01113, 'x', $discarded->Id);

                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'no unequip');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToHand::class), 'to hand');
                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay');
            },
        ];
    }
}
