<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02042;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02042;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGambleCardsRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02042_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02042';
    }

    /**
     * @return array{0:_02042,1:Reaction_02042,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $ivy = $world->placeCharacter(new _02042(), Game::LOCATION_CITY_DOCKS, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_02042 $reaction */
        $reaction = $ivy->getReactions()[0];
        return [$ivy, $reaction, $actor];
    }

    private function sorcery(TestWorld $world): _02041
    {
        $card = new _02041();
        $placed = $world->placeCard($card, Game::LOCATION_HAND, 1);
        $placed->addTrait($world->game, 'Sorcery');
        return $placed;
    }

    private function reveal(TestWorld $world, int $actorId, array $cardIds): EventDuelGambleCardsRevealed
    {
        $event = new EventDuelGambleCardsRevealed();
        $event->actorId = $actorId;
        $event->playerId = 1;
        $event->revealedCardIds = $cardIds;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_02042(), 'CardReaction');
            },

            // Ivy herself is the Sorcerer at the location, so a revealed Sorcery is enough.
            'revealed Sorcery at Ivy location offers take-or-pass' => function () {
                $world = new TestWorld();
                [$ivy, $reaction, $actor] = $this->scene($world);
                $sorcery = $this->sorcery($world);
                $plain = $world->placeCard(new _02041(), Game::LOCATION_HAND, 1);

                $reaction->handleEvent($this->reveal($world, $actor->Id, [$plain->Id, $sorcery->Id]));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($ivy->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'player');

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['takeSorcery-' . $sorcery->Id, 'pass'], $ids, 'buttons');
            },

            'does not offer without a Sorcery, off location, for an opponent, outside the city, or when used' => function () {
                $world = new TestWorld();
                [$ivy, $reaction, $actor] = $this->scene($world);
                $plain = $world->placeCard(new _02041(), Game::LOCATION_HAND, 1);

                $reaction->handleEvent($this->reveal($world, $actor->Id, [$plain->Id]));
                Assert::count(0, $world->theah->queuedEvents, 'no sorcery');

                $sorcery = $this->sorcery($world);
                $actor->Location = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($this->reveal($world, $actor->Id, [$sorcery->Id]));
                Assert::count(0, $world->theah->queuedEvents, 'different location');

                $actor->Location = Game::LOCATION_CITY_DOCKS;
                $actor->ControllerId = 2;
                $reaction->handleEvent($this->reveal($world, $actor->Id, [$sorcery->Id]));
                Assert::count(0, $world->theah->queuedEvents, 'opponent');

                $actor->ControllerId = 1;
                $ivy->Location = Game::LOCATION_HAND;
                $reaction->handleEvent($this->reveal($world, $actor->Id, [$sorcery->Id]));
                Assert::count(0, $world->theah->queuedEvents, 'ivy not in city');

                $ivy->Location = Game::LOCATION_CITY_DOCKS;
                $reaction->Used = true;
                $reaction->handleEvent($this->reveal($world, $actor->Id, [$sorcery->Id]));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'pass clears the offer and does not mark the reaction used' => function () {
                $world = new TestWorld();
                [, $reaction, $actor] = $this->scene($world);
                $sorcery = $this->sorcery($world);
                $reaction->handleEvent($this->reveal($world, $actor->Id, [$sorcery->Id]));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'unused');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['pass'], $ids, 'cleared');
            },

            // WHY: Taking the card shrinks GAMBLE_REVEAL_COUNT so the later pick sees one fewer reveal.
            'taking a revealed Sorcery puts it in hand and marks the reaction used' => function () {
                $world = new TestWorld();
                [$ivy, $reaction, $actor] = $this->scene($world);
                $sorcery = $this->sorcery($world);
                $world->game->globals->set(Game::GAMBLE_REVEAL_COUNT, 3);
                $reaction->handleEvent($this->reveal($world, $actor->Id, [$sorcery->Id]));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'takeSorcery-' . $sorcery->Id);

                $added = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::count(1, $added, 'hand');
                Assert::same($sorcery->Id, $added[0]->cardId, 'card');
                Assert::same($ivy->ControllerId, $added[0]->playerId, 'player');
                Assert::same(2, $world->game->globals->get(Game::GAMBLE_REVEAL_COUNT), 'count');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: An id that was not in the revealed Sorcery list ends the reaction without taking or using it.
            'an unknown Sorcery id ends the reaction without taking the card' => function () {
                $world = new TestWorld();
                [, $reaction, $actor] = $this->scene($world);
                $sorcery = $this->sorcery($world);
                $reaction->handleEvent($this->reveal($world, $actor->Id, [$sorcery->Id]));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'takeSorcery-9999');

                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToHand::class), 'not taken');
                Assert::false($reaction->Used, 'unused');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['takeSorcery-' . $sorcery->Id, 'pass'], $ids, 'offer kept');
            },
        ];
    }
}
