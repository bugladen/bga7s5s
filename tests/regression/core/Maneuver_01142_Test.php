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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01142;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01142;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01142_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01142';
    }

    /** @return array{0:_01142,1:Maneuver_01142,2:Character,3:Character} */
    private function duel(TestWorld $world, int $actorCombat = 3, int $foeCombat = 2): array
    {
        $risk = $world->placeCard(new _01142(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $actor->ModifiedCombat = $actorCombat;
        $foe->ModifiedCombat = $foeCombat;
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01142 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function equip(TestWorld $world, Attachment $attachment, Character $host): Attachment
    {
        $placed = $world->placeCard($attachment, Game::LOCATION_CITY_DOCKS, $host->ControllerId);
        $placed->AttachedToId = $host->Id;
        $host->Attachments[] = $placed->Id;
        return $placed;
    }

    public function tests(): array
    {
        return [
            'targets cards (not characters)' => function () {
                Assert::instanceOf(IAbilityThatTargetsCards::class, new Maneuver_01142(), 'targets cards');
            },

            // WHY: card text is greater or equal Combat — tie is playable.
            'available when actor Combat is greater or equal and adversary has an attachment' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world, 2, 2);
                $this->equip($world, new _01049(), $foe);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'tie Combat + attachment');
            },

            'available when actor Combat strictly exceeds the adversary' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world, 3, 1);
                $this->equip($world, new _01047(), $foe);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'higher Combat');
            },

            'unavailable when actor Combat is lower' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world, 1, 3);
                $this->equip($world, new _01049(), $foe);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'lower');
            },

            'unavailable when the adversary has no attachments' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 3, 1);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'bare adversary');
            },

            'unavailable when only the actor holds an attachment' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world, 3, 1);
                $this->equip($world, new _01049(), $actor);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'own attachment');
            },

            'resolve queues transition 01142 for the owner' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $this->equip($world, new _01049(), $foe);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->adversaryId = $foe->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01142', $transitions[0]->transition, 'name');
                Assert::same(1, $transitions[0]->playerId, 'owner chooses');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'internal');
            },

            'resolve for a different maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'other';
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            'args list every attachment on the adversary' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->duel($world);
                $weapon = $this->equip($world, new _01049(), $foe);
                $armor = $this->equip($world, new _01047(), $foe);
                $this->equip($world, new _01049(), $actor);

                $args = $maneuver->getArgsFromManeuver(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01142,
                    'duelResolveManeuver_01142'
                );

                $ids = array_column($args['attachments'], 'id');
                Assert::count(2, $ids, 'both foe attachments');
                Assert::true(in_array($weapon->Id, $ids, true), 'weapon');
                Assert::true(in_array($armor->Id, $ids, true), 'armor');
            },

            'act unequips then discards the chosen attachment as an effect' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $weapon = $this->equip($world, new _01049(), $foe);

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01142,
                    'duelResolveManeuver_01142',
                    $weapon->Id
                );

                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequip');
                Assert::same($weapon->Id, $unequip[0]->attachmentId, 'attachment');
                Assert::same($foe->Id, $unequip[0]->characterId, 'adversary');

                $discard = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class);
                Assert::count(1, $discard, 'discard');
                Assert::same($weapon->Id, $discard[0]->cardId, 'card');
                Assert::same($risk->Id, $discard[0]->sourceId, 'source');
                Assert::true($discard[0]->asEffect, 'as effect');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'act refuses an attachment not on the adversary' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->duel($world);
                $this->equip($world, new _01049(), $foe);
                $mine = $this->equip($world, new _01049(), $actor);

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId(
                        $world->game,
                        States::DUEL_RESOLVE_MANEUVER_01142,
                        'x',
                        $mine->Id
                    );
                } catch (\BgaUserException | UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'act refuses a non-attachment id' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $this->equip($world, new _01049(), $foe);

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId(
                        $world->game,
                        States::DUEL_RESOLVE_MANEUVER_01142,
                        'x',
                        $foe->Id
                    );
                } catch (\BgaUserException | UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'character id refused');
            },
        ];
    }
}
