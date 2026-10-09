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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01107;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01108;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01109;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01111;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01111;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01111_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01111';
    }

    /**
     * Research in hand; discard pile seeded with three uniquely named cards unless overridden.
     *
     * @return array{0:_01111,1:Action_01111,2:list<\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card>}
     */
    private function scene(TestWorld $world, bool $seedDiscard = true): array
    {
        $research = $world->placeCard(new _01111(), Game::LOCATION_HAND, 1);
        $discard = [];
        if ($seedDiscard) {
            $discard[] = $world->placeCard(new _01107(), $world->game->getPlayerDiscardDeckName(1), 1);
            $discard[] = $world->placeCard(new _01108(), $world->game->getPlayerDiscardDeckName(1), 1);
            $discard[] = $world->placeCard(new _01109(), $world->game->getPlayerDiscardDeckName(1), 1);
        }
        /** @var Action_01111 $action */
        $action = $research->getActions()[0];
        return [$research, $action, $discard];
    }

    public function tests(): array
    {
        return [
            // WHY: availability counts distinct Name values, not card count — Unique copies of one name do not unlock.
            'available when discard has three cards with different names' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'three names');
            },

            'unavailable with fewer than three distinct names in discard' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $world->placeCard(new _01107(), $world->game->getPlayerDiscardDeckName(1), 1);
                $world->placeCard(new _01107(), $world->game->getPlayerDiscardDeckName(1), 1);
                $world->placeCard(new _01108(), $world->game->getPlayerDiscardDeckName(1), 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'only two names');
            },

            'unavailable with an empty discard' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty');
            },

            'unavailable when Research is not in hand' => function () {
                $world = new TestWorld();
                [$research, $action] = $this->scene($world);
                $research->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'unavailable to the opponent' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'not controller');
            },

            'trigger queues transition 01111' => function () {
                $world = new TestWorld();
                [$research, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01111', $transitions[0]->transition, 'name');
                Assert::same($research->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
            },

            'step 1 args list discard cards' => function () {
                $world = new TestWorld();
                [, $action, $discard] = $this->scene($world);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01111,
                    'x'
                );

                Assert::count(3, $args['cards'], 'three');
                $ids = array_map(fn($c) => $c['id'], $args['cards']);
                Assert::true(in_array($discard[0]->Id, $ids, true), 'includes seeded');
            },

            // WHY: Unique-by-Name — choosing two cards that share a Name must throw.
            'step 1 refuses three ids that are not uniquely named' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $a = $world->placeCard(new _01107(), $world->game->getPlayerDiscardDeckName(1), 1);
                $b = $world->placeCard(new _01107(), $world->game->getPlayerDiscardDeckName(1), 1);
                $c = $world->placeCard(new _01108(), $world->game->getPlayerDiscardDeckName(1), 1);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01111,
                        'x',
                        [$a->Id, $b->Id, $c->Id]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'duplicate names');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_CARD), 'not recorded');
            },

            'step 1 refuses a count other than three' => function () {
                $world = new TestWorld();
                [, $action, $discard] = $this->scene($world);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01111,
                        'x',
                        [$discard[0]->Id, $discard[1]->Id]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'need three');
            },

            'step 1 records CHOSEN_CARD for three uniquely named discard cards' => function () {
                $world = new TestWorld();
                [, $action, $discard] = $this->scene($world);
                $ids = [$discard[0]->Id, $discard[1]->Id, $discard[2]->Id];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01111,
                    'x',
                    $ids
                );

                Assert::same($ids, $world->game->globals->get(Game::CHOSEN_CARD), 'recorded');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'step 2 args list opponents only' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->game->activePlayerId = 1;

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01111_2,
                    'x'
                );

                Assert::count(1, $args['opponents'], 'one opponent in 2p');
                Assert::same(2, $args['opponents'][0]['id'], 'player 2');
            },

            'step 2 refuses selecting yourself' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->game->activePlayerId = 1;

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01111_2,
                        'x',
                        1
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'self');
            },

            'step 2 hands the pick to the chosen opponent via 01111_3' => function () {
                $world = new TestWorld();
                [$research, $action] = $this->scene($world);
                $world->game->activePlayerId = 1;

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01111_2,
                    'x',
                    2
                );

                Assert::same(2, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'opponent');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'hand-off');
                Assert::same('01111_3', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'opponent acts');
                Assert::same($research->Id, $transitions[0]->sourceId, 'source');
            },

            'step 3 puts the chosen researched card into hand and resolves' => function () {
                $world = new TestWorld();
                [$research, $action, $discard] = $this->scene($world);
                $ids = [$discard[0]->Id, $discard[1]->Id, $discard[2]->Id];
                $world->game->globals->set(Game::CHOSEN_CARD, $ids);
                $world->game->activePlayerId = 2;

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01111_3,
                    'x',
                    $discard[1]->Id
                );

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                Assert::count(1, $removed, 'removed');
                Assert::same($discard[1]->Id, $removed[0]->cardId, 'chosen');
                $added = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::count(1, $added, 'to hand');
                Assert::same($discard[1]->Id, $added[0]->cardId, 'chosen');
                Assert::same(1, $added[0]->playerId, 'Research controller');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
                // WHY: locker send is on the card's AsPlayed discard handler, not this opponent-pick step.
            },

            'step 3 refuses an id not among the three researched cards' => function () {
                $world = new TestWorld();
                [, $action, $discard] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, [$discard[0]->Id, $discard[1]->Id, $discard[2]->Id]);
                $outsider = $world->placeCharacter(
                    new GenericCharacter('Outsider'),
                    $world->game->getPlayerDiscardDeckName(1),
                    1
                );

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01111_3,
                        'x',
                        $outsider->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in chosen set');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
