<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Terraria;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Terraria;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01160;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01160;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_05Terraria_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Terraria';
    }

    /** @return array{0:GenericCharacter,1:_05Terraria,2:Reaction_05Terraria} */
    private function attach(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), $location, 1);
        $bottle = $world->placeCard(new _05Terraria(), $location, 1);
        $bottle->AttachedToId = $host->Id;
        /** @var Reaction_05Terraria $reaction */
        $reaction = $bottle->getReactions()[0];
        return [$host, $bottle, $reaction];
    }

    private function targeted(TestWorld $world, int $playerId, int $targetId, int $sourceId, string $abilityId): EventCharacterTargeted
    {
        $event = new EventCharacterTargeted();
        $event->playerId = $playerId;
        $event->targetId = $targetId;
        $event->sourceId = $sourceId;
        $event->abilityId = $abilityId;
        $event->batchId = 7;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Castille FactionAttachment with Covert Reaction' => function () {
                $bottle = new _05Terraria();
                Assert::instanceOf(FactionAttachment::class, $bottle, 'FactionAttachment');
                Assert::instanceOf(IHasReactions::class, $bottle, 'reactions');
                Assert::same(1, $bottle->WealthCost, 'cost');
                Assert::same(0, $bottle->Riposte, 'riposte');
                Assert::same(2, $bottle->Parry, 'parry');
                Assert::same(1, $bottle->Thrust, 'thrust');
                Assert::true($bottle->hasTrait('Alquimia'), 'Alquimia');
                Assert::true($bottle->hasTrait('Invention'), 'Invention');
                Assert::count(1, $bottle->getReactions(), 'one reaction');
                Assert::instanceOf(Reaction_05Terraria::class, $bottle->getReactions()[0], 'reaction');
            },

            'getEquipDiscount is +1 while controller has an Academic and bottle is not attached' => function () {
                $world = new TestWorld();
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_FORUM, 1);
                $bottle = $world->placeCard(new _05Terraria(), Game::LOCATION_HAND, 1);

                $explanations = [];
                $discount = $bottle->getEquipDiscount($world->theah, $performer, $bottle, $explanations);
                Assert::same(1, $discount, 'discount');
                Assert::true(count($explanations) >= 1, 'explanation');
            },

            'getEquipDiscount is 0 without an Academic' => function () {
                $world = new TestWorld();
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $bottle = $world->placeCard(new _05Terraria(), Game::LOCATION_HAND, 1);

                $explanations = [];
                $discount = $bottle->getEquipDiscount($world->theah, $performer, $bottle, $explanations);
                Assert::same(0, $discount, 'no academic');
            },

            'getEquipDiscount does not apply once the bottle is already attached' => function () {
                $world = new TestWorld();
                [$host, $bottle] = $this->attach($world);
                $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_FORUM, 1);
                $other = $world->placeCard(new _05Terraria(), Game::LOCATION_HAND, 1);

                // WHY: Character shadow bug would pass equipped bottle as $attachment —
                // isAttached gate must keep discount at 0 for other equips.
                $explanations = [];
                $discount = $bottle->getEquipDiscount($world->theah, $host, $bottle, $explanations);
                Assert::same(0, $discount, 'already attached self');

                $explanations = [];
                $discount = $bottle->getEquipDiscount($world->theah, $host, $other, $explanations);
                Assert::same(0, $discount, 'other attachment');
            },

            'reaction offers when equipped host is targeted by an opposing ability' => function () {
                $world = new TestWorld();
                [$host, $bottle, $reaction] = $this->attach($world);
                // WHY: Torvo's City Action implements IAbilityThatTargetsCharacters — real
                // source+ability path for shouldReactToAbility (not BasicChallenge fallback).
                $torvo = $world->placeCharacter(new _02034(), Game::LOCATION_CITY_DOCKS, 2);
                $action = $torvo->getActions()[0];

                $event = $this->targeted($world, 2, $host->Id, $torvo->Id, $action->Id);
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($bottle->Id, $transitions[0]->sourceId, 'source');
                Assert::true($event->canceled, 'canceled first');
                Assert::true($reaction->characterTargetedEvent !== null, 'held');
            },

            // WHY: Bleed Out used to wound without EventCharacterTargeted — Cloud Bottle
            // never offered. Act path now emits the hook; reaction must accept Action_01160.
            'reaction offers when Bleed Out targets the equipped host' => function () {
                $world = new TestWorld();
                [$host, $bottle, $reaction] = $this->attach($world);
                $host->Wounds = 1;
                $risk = $world->placeCard(new _01160(), Game::LOCATION_HAND, 2);
                /** @var Action_01160 $action */
                $action = $risk->getActions()[0];

                $event = $this->targeted($world, 2, $host->Id, $risk->Id, $action->Id);
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($bottle->Id, $transitions[0]->sourceId, 'source');
                Assert::true($event->canceled, 'canceled first');
            },

            // WHY: Vantage Point used to engage/wound without EventCharacterTargeted —
            // Cloud Bottle never offered. Reaction_04020 now emits the hook on pick.
            'reaction offers when Vantage Point targets the equipped host' => function () {
                $world = new TestWorld();
                [$host, $bottle, $reaction] = $this->attach($world);
                $risk = $world->placeCard(new _04020(), Game::LOCATION_HAND, 2);
                /** @var Reaction_04020 $vantage */
                $vantage = $risk->getReactions()[0];

                $event = $this->targeted($world, 2, $host->Id, $risk->Id, $vantage->Id);
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($bottle->Id, $transitions[0]->sourceId, 'source');
                Assert::true($event->canceled, 'canceled first');
            },

            'reaction does not offer at a controlled City location' => function () {
                $world = new TestWorld();
                [$host, , $reaction] = $this->attach($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                $torvo = $world->placeCharacter(new _02034(), Game::LOCATION_CITY_DOCKS, 2);
                $action = $torvo->getActions()[0];

                $event = $this->targeted($world, 2, $host->Id, $torvo->Id, $action->Id);
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'controlled');
            },

            'reaction does not offer for own targeting' => function () {
                $world = new TestWorld();
                [$host, , $reaction] = $this->attach($world);
                $torvo = $world->placeCharacter(new _02034(), Game::LOCATION_CITY_DOCKS, 1);
                $action = $torvo->getActions()[0];

                $event = $this->targeted($world, 1, $host->Id, $torvo->Id, $action->Id);
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'own');
            },

            'cancel destroys the bottle and clears held targeting' => function () {
                $world = new TestWorld();
                [$host, $bottle, $reaction] = $this->attach($world);

                $held = $this->targeted($world, 2, $host->Id, 99, 'Action_02034');
                unset($held->theah);
                $reaction->characterTargetedEvent = $held;
                $reaction->SourceId = 99;
                $reaction->TargetId = $host->Id;
                $bottle->IsUpdated = true;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'cancel');

                $unequips = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequips, 'unequip');
                Assert::same($bottle->Id, $unequips[0]->attachmentId, 'unequip id');

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($bottle->Id, $discards[0]->cardId, 'discard id');
                Assert::true($reaction->characterTargetedEvent === null, 'cleared hold');
            },

            'pass re-queues the held EventCharacterTargeted' => function () {
                $world = new TestWorld();
                [$host, $bottle, $reaction] = $this->attach($world);

                $held = $this->targeted($world, 2, $host->Id, 99, 'Action_02034');
                unset($held->theah);
                $reaction->characterTargetedEvent = $held;
                $reaction->TargetId = $host->Id;
                $bottle->IsUpdated = true;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                $queued = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $queued, 're-queued');
                Assert::same($host->Id, $queued[0]->targetId, 'target');
                Assert::true($reaction->skipNextEvent, 'skip');
            },
        ];
    }
}
