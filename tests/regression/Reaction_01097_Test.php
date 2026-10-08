<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01097;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01097;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01097_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01097';
    }

    /**
     * Sanjay (player 1) with a friendly source card; player 2 has a hand card to discard.
     *
     * @return array{0:_01097,1:Reaction_01097,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $sanjay = $world->placeCharacter(new _01097(), Game::LOCATION_CITY_DOCKS, 1);
        $source = $world->placeCharacter(new GenericCharacter('Source'), Game::LOCATION_CITY_DOCKS, 1);
        $discarded = $world->placeCharacter(new GenericCharacter('Discarded'), Game::LOCATION_HAND, 2);
        /** @var Reaction_01097 $reaction */
        $reaction = $sanjay->getReactions()[0];
        return [$sanjay, $reaction, $source, $discarded];
    }

    private function discard(TestWorld $world, int $ownerId, int $cardId, int $sourceId, bool $asEffect = true): EventCardDiscardedFromHand
    {
        $event = new EventCardDiscardedFromHand();
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
            'offers a draw when an opponent discards from hand due to a card you control' => function () {
                $world = new TestWorld();
                [$sanjay, $reaction, $source, $discarded] = $this->scene($world);

                $reaction->handleEvent($this->discard($world, 2, $discarded->Id, $source->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($sanjay->Id, $transitions[0]->sourceId, 'source Sanjay');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
                Assert::same('reaction', $transitions[0]->transition, 'reaction transition');
                Assert::same($sanjay->ControllerId, $transitions[0]->playerId, 'Sanjay controller decides');
            },

            'Sanjay himself counts as the source of "your effect"' => function () {
                $world = new TestWorld();
                [$sanjay, $reaction, , $discarded] = $this->scene($world);

                $reaction->handleEvent($this->discard($world, 2, $discarded->Id, $sanjay->Id));

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            // WHY (journal 2026-04-09): "due to your effect" - payment / framework discards (asEffect=false) never trigger.
            'does not offer when the discard is not an effect' => function () {
                $world = new TestWorld();
                [, $reaction, $source, $discarded] = $this->scene($world);

                $reaction->handleEvent($this->discard($world, 2, $discarded->Id, $source->Id, false));

                Assert::count(0, $world->theah->queuedEvents, 'asEffect=false');
            },

            'does not offer when the opponent\'s discard was caused by their own card' => function () {
                $world = new TestWorld();
                [, $reaction, , $discarded] = $this->scene($world);
                $theirSource = $world->placeCharacter(new GenericCharacter('Their Source'), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->discard($world, 2, $discarded->Id, $theirSource->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not your effect');
            },

            // WHY: sourceId 0 = framework discard (e.g. end-of-turn hand limit); getCardById(0) is null so it must not trigger.
            'does not offer for a framework discard with sourceId 0' => function () {
                $world = new TestWorld();
                [, $reaction, , $discarded] = $this->scene($world);

                $reaction->handleEvent($this->discard($world, 2, $discarded->Id, 0));

                Assert::count(0, $world->theah->queuedEvents, 'no source');
            },

            'does not offer when the discarder is Sanjay\'s own controller' => function () {
                $world = new TestWorld();
                [, $reaction, $source] = $this->scene($world);
                $mine = $world->placeCharacter(new GenericCharacter('My Card'), Game::LOCATION_HAND, 1);

                $reaction->handleEvent($this->discard($world, 1, $mine->Id, $source->Id));

                Assert::count(0, $world->theah->queuedEvents, 'self-discard');
            },

            // WHY: only hand discards are wired (contrast Reaction_01099a, which also hears play / city-discard events).
            'ignores discards from play' => function () {
                $world = new TestWorld();
                [, $reaction, $source, $discarded] = $this->scene($world);

                $event = new EventCardDiscardedFromPlay();
                $event->ownerId = 2;
                $event->cardId = $discarded->Id;
                $event->sourceId = $source->Id;
                $event->asEffect = true;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'from play ignored');
            },

            'ignores city discard pile events' => function () {
                $world = new TestWorld();
                [, $reaction, $source, $discarded] = $this->scene($world);

                $event = new EventCardAddedToCityDiscardPile();
                $event->playerId = 2;
                $event->cardId = $discarded->Id;
                $event->sourceId = $source->Id;
                $event->asEffect = true;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'city discard ignored');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction, $source, $discarded] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->discard($world, 2, $discarded->Id, $source->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons offer Draw Card and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $ids = array_map(fn($b) => $b['reaction'], $reaction->getReactionButtonProperties($world->theah));

                Assert::same(['drawCard', 'pass'], $ids, 'buttons');
            },

            'drawCard queues a draw for Sanjay\'s controller, marks Used, and finishes' => function () {
                $world = new TestWorld();
                [$sanjay, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'drawCard');

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw queued');
                Assert::same($sanjay->ControllerId, $draws[0]->playerId, 'Sanjay controller draws');
                Assert::true($reaction->Used, 'used');
                Assert::count(1, $world->theah->queuedOfType(EventReactionActivated::class), 'activation announced');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'drawing for Sanjay\'s controller even when the active player is the opponent' => function () {
                $world = new TestWorld();
                [$sanjay, $reaction] = $this->scene($world);
                $world->game->activePlayerId = 2;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'drawCard');

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::same($sanjay->ControllerId, $draws[0]->playerId, 'owner draws, not the active player');
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
