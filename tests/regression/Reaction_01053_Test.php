<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01053_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01053';
    }

    /**
     * Player 1 holds Hexenjagd and has `Mine` at Docks (the Sorcerer ability's target).
     * Player 2 holds Pull the Strand (a Sorcerer ability that targets characters).
     *
     * @return array{0:_01053,1:Reaction_01053,2:Character,3:_01030,4:CardAction}
     */
    private function scene(TestWorld $world): array
    {
        $hex = $world->placeCard(new _01053(), Game::LOCATION_HAND, 1);
        $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $sorcery = $world->placeCard(new _01030(), Game::LOCATION_HAND, 2);

        /** @var Reaction_01053 $reaction */
        $reaction = $hex->getReactions()[0];
        /** @var CardAction $ability */
        $ability = $sorcery->getActions()[0];
        return [$hex, $reaction, $mine, $sorcery, $ability];
    }

    private function sorcererStart(TestWorld $world, Card $source, CardAction $ability, int $targetId): EventSorcererAbilityStart
    {
        $event = new EventSorcererAbilityStart();
        $event->playerId = 2;
        $event->sourceId = $source->Id;
        $event->abilityId = $ability->Id;
        $event->targetId = $targetId;
        $event->theah = $world->theah;
        return $event;
    }

    private function characterTargeted(TestWorld $world, Card $source, CardAction $ability, int $targetId): EventCharacterTargeted
    {
        $event = new EventCharacterTargeted();
        $event->playerId = 2;
        $event->sourceId = $source->Id;
        $event->abilityId = $ability->Id;
        $event->targetId = $targetId;
        $event->theah = $world->theah;
        return $event;
    }

    private function woundEvent(TestWorld $world, int $characterId, int $batchId = 0): EventCharacterBeingWounded
    {
        $event = new EventCharacterBeingWounded();
        $event->characterId = $characterId;
        $event->wounds = 1;
        $event->batchId = $batchId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a cancel reaction' => function () {
                Assert::instanceOf(ICancelReaction::class, new Reaction_01053(), 'ICancelReaction');
            },

            'offers on EventSorcererAbilityStart targeting your location' => function () {
                $world = new TestWorld();
                [$hex, $reaction, $mine, $sorcery, $ability] = $this->scene($world);

                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $mine->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction transition');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'this reaction');
                Assert::same($hex->Id, $transitions[0]->sourceId, 'source is Hexenjagd');
            },

            'buttons offer one cancel per own character at the target location plus decline' => function () {
                $world = new TestWorld();
                [, $reaction, $mine, $sorcery, $ability] = $this->scene($world);
                $buddy = $world->placeCharacter(new GenericCharacter('Buddy'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Away'), Game::LOCATION_CITY_FORUM, 1);
                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $mine->Id));

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("cancel-{$mine->Id}", $ids, true), 'cancel mine');
                Assert::true(in_array("cancel-{$buddy->Id}", $ids, true), 'cancel buddy');
                Assert::true(in_array('decline', $ids, true), 'decline');
                Assert::count(3, $ids, 'no characters from other locations');
            },

            'offers on EventCharacterTargeted for a Sorcerer ability on a character' => function () {
                $world = new TestWorld();
                [, $reaction, $mine, $sorcery, $ability] = $this->scene($world);

                $reaction->handleEvent($this->characterTargeted($world, $sorcery, $ability, $mine->Id));

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offer');
            },

            // WHY: only Sorcerer abilities may be cancelled; a plain Risk Action targeting a character must not offer.
            'does not offer on EventCharacterTargeted for a non-Sorcerer ability' => function () {
                $world = new TestWorld();
                [, $reaction, $mine] = $this->scene($world);
                $plain = $world->placeCard(new _01033(), Game::LOCATION_HAND, 2);
                $plainAbility = $plain->getActions()[0];

                $reaction->handleEvent($this->characterTargeted($world, $plain, $plainAbility, $mine->Id));

                Assert::count(0, $world->theah->queuedEvents, 'no offer');
            },

            'does not offer when target is Home' => function () {
                $world = new TestWorld();
                [, $reaction, , $sorcery, $ability] = $this->scene($world);
                $homeTarget = $world->placeCharacter(new GenericCharacter('Homebody'), Game::LOCATION_PLAYER_HOME, 2);

                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $homeTarget->Id));

                Assert::count(0, $world->theah->queuedEvents, 'Home target');
            },

            'does not offer when no character of yours is at the target location' => function () {
                $world = new TestWorld();
                [, $reaction, , $sorcery, $ability] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $far->Id));

                Assert::count(0, $world->theah->queuedEvents, 'no performer');
            },

            'does not offer when sorcerer start has no target' => function () {
                $world = new TestWorld();
                [, $reaction, , $sorcery, $ability] = $this->scene($world);

                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, 0));

                Assert::count(0, $world->theah->queuedEvents, 'no target');
            },

            // WHY: reaction is played from hand — Hexenjagd in discard/play must not offer.
            'unavailable from discard' => function () {
                $world = new TestWorld();
                [$hex, $reaction, $mine, $sorcery, $ability] = $this->scene($world);
                $hex->Location = $world->game->getPlayerDiscardDeckName(1);

                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $mine->Id));
                $reaction->handleEvent($this->characterTargeted($world, $sorcery, $ability, $mine->Id));

                Assert::count(0, $world->theah->queuedEvents, 'discard');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction, $mine, $sorcery, $ability] = $this->scene($world);
                $reaction->setUsed($world->theah, true);
                $world->theah->takeQueuedEvents();

                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $mine->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'use queues pay state and holds the effects' => function () {
                $world = new TestWorld();
                [$hex, $reaction, $mine, $sorcery, $ability] = $this->scene($world);
                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $mine->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, "cancel-{$mine->Id}");

                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');

                // While holding, a wound on the targeted card is intercepted.
                $wound = $this->woundEvent($world, $mine->Id, 11);
                $reaction->handleEvent($wound);
                Assert::true($wound->canceled, 'wound held');

                // Not re-offered while holding.
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $mine->Id));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no re-offer');
            },

            'held effects only intercept the targeted card' => function () {
                $world = new TestWorld();
                [, $reaction, $mine, $sorcery, $ability] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);
                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $mine->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, "cancel-{$mine->Id}");

                $wound = $this->woundEvent($world, $other->Id);
                $reaction->handleEvent($wound);

                Assert::false($wound->canceled, 'other card not held');
            },

            'held CardMoving on the target is cancelled' => function () {
                $world = new TestWorld();
                [, $reaction, $mine, $sorcery, $ability] = $this->scene($world);
                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $mine->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, "cancel-{$mine->Id}");

                $move = new EventCardMoving();
                $move->cardId = $mine->Id;
                $move->theah = $world->theah;
                $reaction->handleEvent($move);

                Assert::true($move->canceled, 'move held');
            },

            'cost paid wounds the chosen performer, cancels the effects and marks used' => function () {
                $world = new TestWorld();
                [$hex, $reaction, $mine, $sorcery, $ability] = $this->scene($world);
                $buddy = $world->placeCharacter(new GenericCharacter('Buddy'), Game::LOCATION_CITY_DOCKS, 1);
                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $mine->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, "cancel-{$buddy->Id}");
                $held = $this->woundEvent($world, $mine->Id, 5);
                $reaction->handleEvent($held);
                $world->theah->takeQueuedEvents();

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = "cancel-{$buddy->Id}";
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'one wound queued');
                Assert::same($buddy->Id, $wounds[0]->characterId, 'performer wounded, not the target');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same($hex->Id, $wounds[0]->sourceId, 'source Hexenjagd');
                Assert::true($reaction->Used, 'used');

                // Effects are gone: a later wound on the target is no longer held.
                $later = $this->woundEvent($world, $mine->Id);
                $reaction->handleEvent($later);
                Assert::false($later->canceled, 'hold released after resolution');
            },

            'decline releases held effects' => function () {
                $world = new TestWorld();
                [, $reaction, $mine, $sorcery, $ability] = $this->scene($world);
                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $mine->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, "cancel-{$mine->Id}");
                $reaction->handleEvent($this->woundEvent($world, $mine->Id, 5));
                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                $released = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $released, 'wound re-queued');
                Assert::same($mine->Id, $released[0]->characterId, 'same wound');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
