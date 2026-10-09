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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01122;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01122;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01122_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01122';
    }

    /**
     * @return array{0:_01122,1:Reaction_01122,2:_01030,3:CardAction}
     */
    private function scene(TestWorld $world): array
    {
        $torsten = $world->placeCharacter(new _01122(), Game::LOCATION_CITY_DOCKS, 1);
        $sorcery = $world->placeCard(new _01030(), Game::LOCATION_HAND, 2);
        /** @var Reaction_01122 $reaction */
        $reaction = $torsten->getReactions()[0];
        /** @var CardAction $ability */
        $ability = $sorcery->getActions()[0];
        return [$torsten, $reaction, $sorcery, $ability];
    }

    private function sorcererStart(TestWorld $world, Card $source, CardAction $ability, int $targetId, int $batchId = 7): EventSorcererAbilityStart
    {
        $event = new EventSorcererAbilityStart();
        $event->playerId = 2;
        $event->sourceId = $source->Id;
        $event->abilityId = $ability->Id;
        $event->targetId = $targetId;
        $event->batchId = $batchId;
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

    public function tests(): array
    {
        return [
            'is a cancel reaction' => function () {
                Assert::instanceOf(ICancelReaction::class, new Reaction_01122(), 'ICancelReaction');
            },

            'offers on EventSorcererAbilityStart targeting Torsten' => function () {
                $world = new TestWorld();
                [$torsten, $reaction, $sorcery, $ability] = $this->scene($world);

                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $torsten->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($torsten->Id, $transitions[0]->sourceId, 'source Torsten');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            'does not offer when Sorcerer ability targets someone else' => function () {
                $world = new TestWorld();
                [, $reaction, $sorcery, $ability] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $other->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not Torsten');
            },

            'offers on EventCharacterTargeted for a Sorcerer ability' => function () {
                $world = new TestWorld();
                [$torsten, $reaction, $sorcery, $ability] = $this->scene($world);

                $reaction->handleEvent($this->characterTargeted($world, $sorcery, $ability, $torsten->Id));

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            // WHY: card text is "Sorcery or Sorcerer Ability"; EventCharacterTargeted fires for any
            // IAbilityThatTargetsCharacters, so gate on ISorcererAbility.
            'does not offer on EventCharacterTargeted for a non-Sorcerer ability' => function () {
                $world = new TestWorld();
                [$torsten, $reaction] = $this->scene($world);
                $plain = $world->placeCard(new _01033(), Game::LOCATION_HAND, 2);
                $plainAbility = $plain->getActions()[0];

                $reaction->handleEvent($this->characterTargeted($world, $plain, $plainAbility, $torsten->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not sorcerer');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [$torsten, $reaction, $sorcery, $ability] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $torsten->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons offer Cancel and Decline' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['cancel', 'decline'], $ids, 'buttons');
            },

            'cancel marks Used, clears the batch, and finishes' => function () {
                $world = new TestWorld();
                [$torsten, $reaction, $sorcery, $ability] = $this->scene($world);
                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $torsten->Id, 11));
                // Leave a pending transition from the sorcery source so deleteTransitionEventsBySourceId has work.
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offer pending');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'cancel');

                Assert::true($reaction->Used, 'used');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'transitions cleared');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'decline leaves the reaction available' => function () {
                $world = new TestWorld();
                [$torsten, $reaction, $sorcery, $ability] = $this->scene($world);
                $reaction->handleEvent($this->sorcererStart($world, $sorcery, $ability, $torsten->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
