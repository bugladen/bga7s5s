<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01089;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01089;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01089_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01089';
    }

    /** @return array{0:_01089,1:Reaction_01089} */
    private function scene(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        $soline = $world->placeCharacter(new _01089(), $location, 1);
        /** @var Reaction_01089 $reaction */
        $reaction = $soline->getReactions()[0];
        return [$soline, $reaction];
    }

    private function resolved(TestWorld $world, int $playerId): EventActionResolved
    {
        $event = new EventActionResolved();
        $event->playerId = $playerId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a card reaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_01089(), 'CardReaction');
            },

            'offers after the controller\'s Action resolves while Soline is in the city' => function () {
                $world = new TestWorld();
                [$soline, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->resolved($world, 1));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction transition');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'this reaction');
                Assert::same($soline->Id, $transitions[0]->sourceId, 'Soline is the source');
                Assert::same(1, $transitions[0]->playerId, 'offered to Soline\'s controller');
            },

            // WHY (2026-04-09 audit): "After an Action resolves" is NOT filtered by player — an opponent's
            // Action also lets Soline slip away.
            'also offers after an opponent\'s Action resolves' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->resolved($world, 2));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same(1, $transitions[0]->playerId, 'still Soline\'s controller decides');
            },

            // WHY: this is a CITY Reaction — at Player Home Soline cannot use it.
            'does not offer while Soline is at Player Home' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world, Game::LOCATION_PLAYER_HOME);

                $reaction->handleEvent($this->resolved($world, 1));
                Assert::count(0, $world->theah->queuedEvents, 'home');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->resolved($world, 1));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'ignores unrelated events' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $event = new \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'buttons list adjacent City locations (no Home) plus Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world, Game::LOCATION_CITY_FORUM);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(
                    ['moveTo-' . Game::LOCATION_CITY_DOCKS, 'moveTo-' . Game::LOCATION_CITY_BAZAAR, 'pass'],
                    $ids,
                    'Forum neighbours, no Home'
                );
            },

            'buttons for a corner location offer its single neighbour' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world, Game::LOCATION_CITY_DOCKS);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['moveTo-' . Game::LOCATION_CITY_FORUM, 'pass'], $ids, 'Docks -> Forum only');
            },

            'moveTo queues a non-engaging move of Soline, logs it and marks the reaction used' => function () {
                $world = new TestWorld();
                [$soline, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveTo-' . Game::LOCATION_CITY_FORUM);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($soline->Id, $moves[0]->cardId, 'Soline moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to');
                // WHY: a reaction move is not an attack — it must not auto-engage or start a duel.
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($soline->Id, $moves[0]->sourceId, 'source');
                Assert::same($reaction->Id, $moves[0]->abilityId, 'ability');

                Assert::true($reaction->Used, 'used');
                Assert::count(1, $world->theah->queuedOfType(EventReactionUsed::class), 'used event');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done transition');
                $types = array_column($world->game->notify->messages, 'type');
                Assert::true(in_array('message', $types, true), 'logged');
            },

            'pass moves nothing and does not mark the reaction used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done transition');
            },
        ];
    }
}
