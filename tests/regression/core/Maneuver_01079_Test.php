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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01079;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01079;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01079_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01079';
    }

    /** @return array{0:_01079,1:Maneuver_01079,2:Character,3:Character} */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01079(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;

        /** @var Maneuver_01079 $maneuver */
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

    private function resolveEvent(TestWorld $world, Maneuver_01079 $maneuver, Character $foe): EventResolveManeuver
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->adversaryId = $foe->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'targets cards, not characters (hook forbids both)' => function () {
                Assert::instanceOf(IAbilityThatTargetsCards::class, new Maneuver_01079(), 'targets cards');
                Assert::false(new Maneuver_01079() instanceof \Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters, 'not characters');
            },

            'available when the adversary has a Weapon equipped' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $this->equip($world, new _01049(), $foe);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'weapon');
            },

            'unavailable when the adversary has no attachments' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'bare');
            },

            'unavailable when the adversary only has a non-Weapon attachment' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $this->equip($world, new _01047(), $foe);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'armor is not a weapon');
            },

            // WHY: the Weapon must be the ADVERSARY's - one on the actor's own character does not count.
            'unavailable when only the actor (not the adversary) holds a Weapon' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $this->equip($world, new _01049(), $actor);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'own weapon');
            },

            'unavailable when the adversary is in discard or locker' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $this->equip($world, new _01049(), $foe);
                $world->game->forceInDiscardOrLocker = true;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'dead adversary');
            },

            'discount is 1 when actor Finesse is strictly greater than the adversary' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);
                $actor->ModifiedFinesse = 3;
                $foe->ModifiedFinesse = 2;

                $explanations = [];
                Assert::same(1, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'discount');
                Assert::count(1, $explanations, 'explained');
            },

            'no discount when Finesse is equal' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);
                $actor->ModifiedFinesse = 2;
                $foe->ModifiedFinesse = 2;

                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'tie');
                Assert::count(0, $explanations, 'no explanation');
            },

            'no discount when Finesse is lower' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);
                $actor->ModifiedFinesse = 1;
                $foe->ModifiedFinesse = 3;

                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'lower');
            },

            // WHY: discount applies only to this card's own Maneuver cost, not other combat cards.
            'no discount when queried for a different combat card' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->duel($world);
                $actor->ModifiedFinesse = 3;
                $foe->ModifiedFinesse = 1;
                $other = $world->placeCard(new _01079(), Game::LOCATION_HAND, 1);

                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $other, $explanations), 'other card');
            },

            'resolve queues transition 01079' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);

                $maneuver->handleEvent($this->resolveEvent($world, $maneuver, $foe));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01079', $transitions[0]->transition, 'name');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'internal id');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same(1, $transitions[0]->playerId, 'controlled by the maneuver owner');
            },

            'resolve for a different maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);

                $event = $this->resolveEvent($world, $maneuver, $foe);
                $event->maneuverId = 'someOtherManeuver';
                $maneuver->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'args list only the adversary\'s Weapons' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $weapon = $this->equip($world, new _01049(), $foe);
                $this->equip($world, new _01047(), $foe);

                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_RESOLVE_MANEUVER_01079, 'x');

                Assert::count(1, $args['attachments'], 'weapon only');
                Assert::same($weapon->Id, $args['attachments'][0]['id'], 'weapon id');
            },

            'step 1 records CHOSEN_ATTACHMENT and hands the choice to the adversary\'s controller' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $weapon = $this->equip($world, new _01049(), $foe);

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01079, 'x', $weapon->Id);

                Assert::same($weapon->Id, $world->game->globals->get(Game::CHOSEN_ATTACHMENT), 'chosen attachment');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01079_2', $transitions[0]->transition, 'second step');
                Assert::same(2, $transitions[0]->playerId, 'adversary\'s player decides destroy-or-wound');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'step 1 refuses an attachment equipped to someone else' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->duel($world);
                $this->equip($world, new _01049(), $foe);
                $mine = $this->equip($world, new _01049(), $actor);

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01079, 'x', $mine->Id);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_ATTACHMENT), 'nothing recorded');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'step 1 refuses an id that is not an attachment' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01079, 'x', $actor->Id);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'character id is not an attachment');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'step 2 id 1 destroys the Weapon: unequip then discard from play, no wound' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $weapon = $this->equip($world, new _01049(), $foe);
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $weapon->Id);

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01079_2, 'x', 1);

                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequipped');
                Assert::same($weapon->Id, $unequip[0]->attachmentId, 'attachment');
                Assert::same($foe->Id, $unequip[0]->characterId, 'from the adversary');

                $discard = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class);
                Assert::count(1, $discard, 'discarded');
                Assert::same($weapon->Id, $discard[0]->cardId, 'weapon discarded');
                Assert::same($risk->Id, $discard[0]->sourceId, 'source is the Risk');
                Assert::true($discard[0]->asEffect, 'as an effect');

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound when destroyed');
                Assert::same(
                    [EventAttachmentUnequipped::class, EventCardDiscardedFromPlay::class],
                    array_map(fn($e) => $e::class, $world->theah->queuedEvents),
                    'unequip precedes discard'
                );
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'step 2 id 2 wounds the adversary and keeps the Weapon' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);
                $weapon = $this->equip($world, new _01049(), $foe);
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $weapon->Id);

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01079_2, 'x', 2);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'one wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary wounded');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $wounds[0]->abilityId, 'ability');
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'not unequipped');
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'not discarded');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'step 2 refuses an id that is neither destroy nor wound' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);
                $weapon = $this->equip($world, new _01049(), $foe);
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $weapon->Id);

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01079_2, 'x', 3);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid choice');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
