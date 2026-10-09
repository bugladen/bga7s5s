<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01099;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01099a;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01099a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01099a';
    }

    /**
     * Shifting Blame (player 1) with a friendly source character and an opposing hand card.
     *
     * @return array{0:_01099,1:Reaction_01099a,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01099(), Game::LOCATION_PLAYER_HOME, 1);
        $source = $world->placeCharacter(new GenericCharacter('Source'), Game::LOCATION_CITY_DOCKS, 1);
        $discarded = $world->placeCharacter(new GenericCharacter('Discarded'), Game::LOCATION_HAND, 2);
        /** @var Reaction_01099a $reaction */
        $reaction = $scheme->getReactions()[0];
        return [$scheme, $reaction, $source, $discarded];
    }

    private function handDiscard(TestWorld $world, int $ownerId, int $cardId, int $sourceId, bool $asEffect = true): EventCardDiscardedFromHand
    {
        $event = new EventCardDiscardedFromHand();
        $event->ownerId = $ownerId;
        $event->cardId = $cardId;
        $event->sourceId = $sourceId;
        $event->asEffect = $asEffect;
        $event->theah = $world->theah;
        return $event;
    }

    private function cityDiscard(TestWorld $world, int $cardId, int $sourceId, bool $asEffect = true): EventCardAddedToCityDiscardPile
    {
        $event = new EventCardAddedToCityDiscardPile();
        $event->cardId = $cardId;
        $event->sourceId = $sourceId;
        $event->asEffect = $asEffect;
        $event->theah = $world->theah;
        return $event;
    }

    private function playDiscard(TestWorld $world, int $ownerId, int $cardId, int $sourceId, bool $asEffect = true): EventCardDiscardedFromPlay
    {
        $event = new EventCardDiscardedFromPlay();
        $event->ownerId = $ownerId;
        $event->cardId = $cardId;
        $event->sourceId = $sourceId;
        $event->asEffect = $asEffect;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            // ---- hand discards ----
            'offers a draw when a hand discard is caused by a card you control' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $source, $discarded] = $this->scene($world);

                $reaction->handleEvent($this->handDiscard($world, 2, $discarded->Id, $source->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source scheme');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
                Assert::same('reaction', $transitions[0]->transition, 'reaction transition');
                Assert::same($scheme->ControllerId, $transitions[0]->playerId, 'scheme controller decides');
            },

            // WHY: text says "a player discards" - unlike Sanjay (01097) it does not require the discarder to be an opponent.
            'also offers when the scheme controller discards due to their own effect' => function () {
                $world = new TestWorld();
                [, $reaction, $source] = $this->scene($world);
                $mine = $world->placeCharacter(new GenericCharacter('My Card'), Game::LOCATION_HAND, 1);

                $reaction->handleEvent($this->handDiscard($world, 1, $mine->Id, $source->Id));

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'any player');
            },

            // WHY (journal 2026-04-09 bug fix): a stray `sourceId == 0` branch used to fire for framework discards,
            // bypassing the "your effect" ownership check. Framework discards are nobody's effect.
            'does NOT offer for a framework discard with sourceId 0' => function () {
                $world = new TestWorld();
                [, $reaction, , $discarded] = $this->scene($world);

                $reaction->handleEvent($this->handDiscard($world, 2, $discarded->Id, 0));

                Assert::count(0, $world->theah->queuedEvents, 'sourceId 0 must not trigger');
            },

            'sourceId 0 city discard does not trigger either' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $cityCard = $world->placeCharacter(new GenericCharacter('City Card'), Game::LOCATION_CITY_DECK, 0);

                $reaction->handleEvent($this->cityDiscard($world, $cityCard->Id, 0));

                Assert::count(0, $world->theah->queuedEvents, 'sourceId 0 must not trigger');
            },

            'does not offer when the discard is not an effect' => function () {
                $world = new TestWorld();
                [, $reaction, $source, $discarded] = $this->scene($world);

                $reaction->handleEvent($this->handDiscard($world, 2, $discarded->Id, $source->Id, false));

                Assert::count(0, $world->theah->queuedEvents, 'asEffect=false');
            },

            'does not offer when the source card belongs to the opponent' => function () {
                $world = new TestWorld();
                [, $reaction, , $discarded] = $this->scene($world);
                $theirSource = $world->placeCharacter(new GenericCharacter('Their Source'), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->handDiscard($world, 2, $discarded->Id, $theirSource->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not your effect');
            },

            'does not offer when the source card is unknown' => function () {
                $world = new TestWorld();
                [, $reaction, , $discarded] = $this->scene($world);

                $reaction->handleEvent($this->handDiscard($world, 2, $discarded->Id, 999999));

                Assert::count(0, $world->theah->queuedEvents, 'unknown source');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction, $source, $discarded] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->handDiscard($world, 2, $discarded->Id, $source->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // ---- discard from play ----
            'offers when a card is discarded from play due to your effect' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $source] = $this->scene($world);
                $inPlay = $world->placeCharacter(new GenericCharacter('In Play'), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->playDiscard($world, 2, $inPlay->Id, $source->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source scheme');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            'from-play discard that is not an effect does not trigger' => function () {
                $world = new TestWorld();
                [, $reaction, $source] = $this->scene($world);
                $inPlay = $world->placeCharacter(new GenericCharacter('In Play'), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->playDiscard($world, 2, $inPlay->Id, $source->Id, false));

                Assert::count(0, $world->theah->queuedEvents, 'asEffect=false');
            },

            // ---- city discard pile ----
            'offers when an uncontrolled city card is discarded due to your effect' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $source] = $this->scene($world);
                $cityCard = $world->placeCharacter(new GenericCharacter('City Card'), Game::LOCATION_CITY_DECK, 0);

                $reaction->handleEvent($this->cityDiscard($world, $cityCard->Id, $source->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            // WHY: a player-controlled card entering the city discard is a play-area discard, not "a player discards a card".
            'ignores a controlled card entering the city discard pile' => function () {
                $world = new TestWorld();
                [, $reaction, $source, $discarded] = $this->scene($world);

                $reaction->handleEvent($this->cityDiscard($world, $discarded->Id, $source->Id));

                Assert::count(0, $world->theah->queuedEvents, 'controlled card ignored');
            },

            'city discard that is not an effect does not trigger' => function () {
                $world = new TestWorld();
                [, $reaction, $source] = $this->scene($world);
                $cityCard = $world->placeCharacter(new GenericCharacter('City Card'), Game::LOCATION_CITY_DECK, 0);

                $reaction->handleEvent($this->cityDiscard($world, $cityCard->Id, $source->Id, false));

                Assert::count(0, $world->theah->queuedEvents, 'asEffect=false');
            },

            'city discard caused by an opponent card does not trigger' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $theirSource = $world->placeCharacter(new GenericCharacter('Their Source'), Game::LOCATION_CITY_DOCKS, 2);
                $cityCard = $world->placeCharacter(new GenericCharacter('City Card'), Game::LOCATION_CITY_DECK, 0);

                $reaction->handleEvent($this->cityDiscard($world, $cityCard->Id, $theirSource->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not your effect');
            },

            // ---- description / buttons ----
            'description names the discarded card once one is recorded' => function () {
                $world = new TestWorld();
                [, $reaction, $source, $discarded] = $this->scene($world);

                Assert::notContains('Discarded', $reaction->getReactionDescription($world->theah), 'nothing recorded yet');

                $reaction->handleEvent($this->handDiscard($world, 2, $discarded->Id, $source->Id));

                Assert::contains('Discarded', $reaction->getReactionDescription($world->theah), 'card named');
            },

            'buttons offer Draw Card and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $ids = array_map(fn($b) => $b['reaction'], $reaction->getReactionButtonProperties($world->theah));

                Assert::same(['drawCard', 'pass'], $ids, 'buttons');
            },

            // ---- performReaction ----
            'drawCard queues a draw, marks Used, announces and finishes' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);
                $world->game->activePlayerId = 1;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'drawCard');

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw queued');
                Assert::same(1, $draws[0]->playerId, 'reacting player draws');
                Assert::true($reaction->Used, 'used');
                Assert::count(1, $world->theah->queuedOfType(EventReactionActivated::class), 'activation announced');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: several discards can queue several offers; once the draw is taken the siblings must disappear.
            'drawCard deletes other pending offers of this reaction but keeps unrelated transitions' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);
                $mine = EventFactory::createReactionTransitionEvent(1, $scheme->Id, $reaction->Id);
                $other = EventFactory::createReactionTransitionEvent(1, $scheme->Id, 'someOtherReaction');
                $world->theah->queueEvent($mine);
                $world->theah->queueEvent($other);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'drawCard');

                $internalIds = array_map(
                    fn($e) => $e->internalId,
                    $world->theah->queuedOfType(EventTransition::class)
                );
                Assert::false(in_array($reaction->Id, $internalIds, true), 'own offer removed');
                Assert::true(in_array('someOtherReaction', $internalIds, true), 'other transitions kept');
            },

            'drawCard when already used draws nothing' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'drawCard');

                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no second draw');
                Assert::same(['done'], $world->game->gamestate->transitions, 'still finishes');
            },

            'pass draws nothing and leaves the reaction available' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
