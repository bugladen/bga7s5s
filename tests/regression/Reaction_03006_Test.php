<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02061;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03006_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03006';
    }

    /**
     * @return array{0:_03006,1:Reaction_03006,2:GenericCharacter,3:_03001}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _03006(), Game::LOCATION_PLAYER_HOME, 1);
        $strega = $world->placeCharacter(new GenericCharacter('Strand', ['Strega']), Game::LOCATION_CITY_DOCKS, 1);
        $target = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $source = $world->placeCharacter(new _03001(), Game::LOCATION_CITY_FORUM, 2);
        $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
        $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
        /** @var Reaction_03006 $reaction */
        $reaction = $scheme->getReactions()[0];
        return [$scheme, $reaction, $target, $source];
    }

    private function wound(TestWorld $world, _03001 $source, int $targetId, bool $canceled = false): EventCharacterBeingWounded
    {
        $event = new EventCharacterBeingWounded();
        $event->theah = $world->theah;
        $event->sourceId = $source->Id;
        $event->characterId = $targetId;
        $event->abilityId = $source->getActions()[0]->Id;
        $event->wounds = 1;
        $event->canceled = $canceled;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a card reaction whose stage fields are public' => function () {
                $reaction = new Reaction_03006();
                Assert::instanceOf(CardReaction::class, $reaction, 'reaction');
                Assert::same('', $reaction->stage, 'idle');
                Assert::same(0, $reaction->opponentId, 'opponent');
            },

            'offers when an opponent character-targeting ability hits your character beside your Strega and their hand is larger' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $target, $source] = $this->scene($world);
                $reaction->handleEvent($this->wound($world, $source, $target->Id));

                Assert::same('offer', $reaction->stage, 'offer');
                Assert::same(2, $reaction->opponentId, 'opponent');
                Assert::same($target->Id, $reaction->targetCharacterId, 'target');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same(1, $transitions[0]->playerId, 'owner chooses');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['sink', 'pass'], $buttons, 'buttons');
            },

            'equal hands, no Strega, a friendly source, an empty ability, or a canceled wound do not offer' => function () {
                $world = new TestWorld();
                [, $reaction, $target, $source] = $this->scene($world);
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 1);
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 1);
                $reaction->handleEvent($this->wound($world, $source, $target->Id));
                Assert::same('', $reaction->stage, 'hands are equal');

                foreach ($world->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, 1) as $card) {
                    $card->Location = 'Discard-1';
                }
                $target->Location = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($this->wound($world, $source, $target->Id));
                Assert::same('', $reaction->stage, 'Strega is at the Docks');
                $target->Location = Game::LOCATION_CITY_DOCKS;

                $source->ControllerId = 1;
                $reaction->handleEvent($this->wound($world, $source, $target->Id));
                Assert::same('', $reaction->stage, 'not an opponent');
                $source->ControllerId = 2;

                $event = $this->wound($world, $source, $target->Id);
                $event->abilityId = '';
                $reaction->handleEvent($event);
                Assert::same('', $reaction->stage, 'no ability');

                $reaction->handleEvent($this->wound($world, $source, $target->Id, true));
                Assert::same('', $reaction->stage, 'canceled');
            },

            // WHY: Basic challenges have sourceId 0 and skip the IAbilityThatTargetsCharacters gate.
            // The opponent is the event's playerId.
            'a basic challenge uses the issuing player when there is no source card' => function () {
                $world = new TestWorld();
                [, $reaction, $target] = $this->scene($world);
                $event = new EventChallengeIssued();
                $event->theah = $world->theah;
                $event->playerId = 2;
                $event->defenderId = $target->Id;
                $event->sourceId = 0;
                $event->abilityId = 'BasicChallenge';
                $reaction->handleEvent($event);
                Assert::same('offer', $reaction->stage, 'offered');
                Assert::same(2, $reaction->opponentId, 'issuing player');
            },

            'force sink hands the pick to the opponent at high priority and does not spend yet' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $target, $source] = $this->scene($world);
                $reaction->handleEvent($this->wound($world, $source, $target->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'sink');
                Assert::same('pick1', $reaction->stage, 'first pick');
                Assert::false($reaction->Used, 'not spent');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same(2, $transitions[0]->playerId, 'opponent picks');
                Assert::same(Event::HIGH_PRIORITY, $transitions[0]->priority, 'ahead of other reactions');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: A sink click while the opponent is already active is treated as a desync
            // repair and advances the pick. A third player, and an opponent Pass, throw.
            'offer clicks are limited to the owner, except the opponent sink repair' => function () {
                $world = new TestWorld();
                [, $reaction, $target, $source] = $this->scene($world);
                $reaction->handleEvent($this->wound($world, $source, $target->Id));
                $world->game->activePlayerId = 3;
                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, $reaction->Id, 'sink');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'third player');
                Assert::same('offer', $reaction->stage, 'still offering');

                $world->game->activePlayerId = 2;
                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'opponent cannot pass');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'sink');
                Assert::same('pick1', $reaction->stage, 'desync repair advances');
                Assert::false($reaction->Used, 'not spent');
            },

            'pass by the owner clears the latch and does not spend' => function () {
                $world = new TestWorld();
                [, $reaction, $target, $source] = $this->scene($world);
                $reaction->handleEvent($this->wound($world, $source, $target->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::same('', $reaction->stage, 'cleared');
                Assert::same(0, $reaction->opponentId, 'opponent cleared');
                Assert::false($reaction->Used, 'pass');
            },

            'two picks sink those cards onto the faction deck and then spend' => function () {
                $world = new TestWorld();
                [, $reaction, $target, $source] = $this->scene($world);
                $reaction->handleEvent($this->wound($world, $source, $target->Id));
                $hand = array_values($world->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, 2));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'sink');
                $world->game->activePlayerId = 2;
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, "card-{$hand[0]->Id}");
                Assert::same('pick2', $reaction->stage, 'second pick');
                Assert::same(1, $reaction->cardsSunk, 'one sunk');
                Assert::same($world->game->getPlayerFactionDeckName(2), $hand[0]->Location, 'location updated');
                Assert::same($hand[0]->Id, $world->game->deckInserts[0]['id'], 'inserted');
                Assert::false($world->game->deckInserts[0]['onTop'], 'bottom');

                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, "card-{$hand[1]->Id}");
                Assert::true($reaction->Used, 'spent');
                Assert::same('', $reaction->stage, 'reset');
                Assert::same($world->game->getPlayerFactionDeckName(2), $hand[1]->Location, 'second card');
            },

            'a card that is not in the opponent hand throws' => function () {
                $world = new TestWorld();
                [, $reaction, $target, $source] = $this->scene($world);
                $reaction->handleEvent($this->wound($world, $source, $target->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'sink');
                $world->game->activePlayerId = 2;
                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, $reaction->Id, 'card-999');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
                Assert::same(0, $reaction->cardsSunk, 'not counted');
            },
        ];
    }
}
