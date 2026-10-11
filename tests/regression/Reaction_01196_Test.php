<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01196;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01196;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01196_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01196';
    }

    /** @return array{0:_01196,1:Reaction_01196} */
    private function scene(TestWorld $world): array
    {
        $angeline = $world->placeCharacter(new _01196(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_01196 $reaction */
        $reaction = $angeline->getReactions()[0];
        return [$angeline, $reaction];
    }

    private function moved(TestWorld $world, int $cardId, bool $engage = true): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->cardId = $cardId;
        $event->fromLocation = Game::LOCATION_PLAYER_HOME;
        $event->toLocation = Game::LOCATION_CITY_DOCKS;
        $event->engage = $engage;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY (journal 2026-04-01-11): EventCardMoved fires before EventHub flips Engaged,
            // so offer when engage && !Angeline->Engaged.
            'offers when Angeline moves with engage while unengaged' => function () {
                $world = new TestWorld();
                [$angeline, $reaction] = $this->scene($world);
                Assert::false($angeline->Engaged, 'precondition');

                $reaction->handleEvent($this->moved($world, $angeline->Id, true));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($angeline->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'controller');
            },

            'does not offer when the move does not engage' => function () {
                $world = new TestWorld();
                [$angeline, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->moved($world, $angeline->Id, false));

                Assert::count(0, $world->theah->queuedEvents, 'no engage');
            },

            'does not offer when Angeline is already engaged' => function () {
                $world = new TestWorld();
                [$angeline, $reaction] = $this->scene($world);
                $angeline->Engaged = true;

                $reaction->handleEvent($this->moved($world, $angeline->Id, true));

                Assert::count(0, $world->theah->queuedEvents, 'already engaged');
            },

            'does not offer when a different card moves' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->moved($world, $ally->Id, true));

                Assert::count(0, $world->theah->queuedEvents, 'other card');
            },

            'does not offer when Angeline is uncontrolled' => function () {
                $world = new TestWorld();
                [$angeline, $reaction] = $this->scene($world);
                $angeline->ControllerId = 0;

                $reaction->handleEvent($this->moved($world, $angeline->Id, true));

                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled');
            },

            'buttons list Pass and each hand card' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $hand = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $world->game->activePlayerId = 1;

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('pass', $ids, true), 'pass');
                Assert::true(in_array("discardCard-{$hand->Id}", $ids, true), 'hand card');
            },

            // WHY: continuous — never setUsed; natural cost gate is discarding a hand card.
            'discard queues hand discard + engarde without marking Used' => function () {
                $world = new TestWorld();
                [$angeline, $reaction] = $this->scene($world);
                $hand = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $reaction->handleEvent($this->moved($world, $angeline->Id, true));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, "discardCard-{$hand->Id}");

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($hand->Id, $discards[0]->cardId, 'card');
                Assert::same($angeline->Id, $discards[0]->sourceId, 'source');

                $engarde = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engarde, 'engarde');
                Assert::same($angeline->Id, $engarde[0]->cardId, 'Angeline');
                Assert::same($reaction->Id, $engarde[0]->abilityId, 'ability');

                Assert::false($reaction->Used, 'continuous');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass finishes without discard, engarde, or Used' => function () {
                $world = new TestWorld();
                [$angeline, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->moved($world, $angeline->Id, true));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'no discard');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engarde');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'invalid reactionId throws' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, $reaction->Id, 'notADiscard');
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid');
            },
        ];
    }
}
