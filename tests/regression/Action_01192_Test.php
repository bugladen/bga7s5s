<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01192;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01192;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01192_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01192';
    }

    /**
     * @return array{0:_01192,1:Action_01192}
     */
    private function inCity(TestWorld $world, int $controllerId = 1): array
    {
        $gustavo = $world->placeCharacter(new _01192(), Game::LOCATION_CITY_DOCKS, $controllerId);
        /** @var Action_01192 $action */
        $action = $gustavo->getActions()[0];
        return [$gustavo, $action];
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction' => function () {
                Assert::instanceOf(CharacterAction::class, new Action_01192(), 'CharacterAction');
            },

            'available when controlled in the city' => function () {
                $world = new TestWorld();
                [, $action] = $this->inCity($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'city');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                $gustavo = $world->placeCharacter(new _01192(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01192 $action */
                $action = $gustavo->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'unavailable when uncontrolled' => function () {
                $world = new TestWorld();
                $gustavo = $world->placeCharacter(new _01192(), Game::LOCATION_CITY_DOCKS, 0);
                /** @var Action_01192 $action */
                $action = $gustavo->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            'trigger sets MULTI_STATE_INITIATING_PLAYER and queues transition 01192' => function () {
                $world = new TestWorld();
                [$gustavo, $action] = $this->inCity($world);
                $risk = $world->placeCard(new _01026(), 'Deck-1', 1);
                $world->game->topFactionCards = [['id' => $risk->Id]];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(1, $world->game->globals->get(Game::MULTI_STATE_INITIATING_PLAYER), 'initiating');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01192', $transitions[0]->transition, 'name');
                Assert::same($gustavo->Id, $transitions[0]->sourceId, 'source');
            },

            'args expose top ModifiedInfluence cards' => function () {
                $world = new TestWorld();
                [$gustavo, $action] = $this->inCity($world);
                $a = $world->placeCard(new _01026(), 'Deck-1', 1);
                $b = $world->placeCard(new _01049(), 'Deck-1', 1);
                $c = $world->placeCard(new _01026(), 'Deck-1', 1);
                $world->game->topFactionCards = [
                    ['id' => $a->Id],
                    ['id' => $b->Id],
                    ['id' => $c->Id],
                ];
                // Influence 2 → only top 2
                Assert::same(2, $gustavo->ModifiedInfluence, 'influence');

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01192_3,
                    'highDramaPlayerTurn_01192_3'
                );

                Assert::count(2, $args['cards'], 'top 2');
                Assert::same($a->Id, $args['cards'][0]['id'], 'first');
                Assert::same($b->Id, $args['cards'][1]['id'], 'second');
            },

            // WHY (journal 2026-04-08-06 / Otto 01038 twin): pick Risk → hand; sink rest; ActionResolved.
            'choose Risk puts into hand and sinks the rest' => function () {
                $world = new TestWorld();
                [$gustavo, $action] = $this->inCity($world);
                // WHY: printed Influence is 2; bump ModifiedInfluence so three top cards are revealed.
                $gustavo->ModifiedInfluence = 3;
                $risk = $world->placeCard(new _01026(), 'Deck-1', 1);
                $att = $world->placeCard(new _01049(), 'Deck-1', 1);
                $other = $world->placeCard(new _01026(), 'Deck-1', 1);
                $world->game->topFactionCards = [
                    ['id' => $risk->Id],
                    ['id' => $att->Id],
                    ['id' => $other->Id],
                ];

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01192_3,
                    'highDramaPlayerTurn_01192_3',
                    $risk->Id
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerFactionDeck::class), 'remove');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToHand::class), 'hand');
                $sinks = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class);
                Assert::count(2, $sinks, 'sink rest');
                foreach ($sinks as $sink) {
                    Assert::false($sink->onTop, 'bottom');
                }
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'chosen');
            },

            // WHY (journal 2026-04-08-06): pass must sink all revealed cards, not leave them on top.
            'pass sinks all revealed cards and ActionResolved' => function () {
                $world = new TestWorld();
                [, $action] = $this->inCity($world);
                $a = $world->placeCard(new _01026(), 'Deck-1', 1);
                $b = $world->placeCard(new _01049(), 'Deck-1', 1);
                $world->game->topFactionCards = [
                    ['id' => $a->Id],
                    ['id' => $b->Id],
                ];

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_01192_3);

                $sinks = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class);
                Assert::count(2, $sinks, 'sink all');
                foreach ($sinks as $sink) {
                    Assert::false($sink->onTop, 'bottom');
                }
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass');
            },

            'act refuses a card not among the top cards' => function () {
                $world = new TestWorld();
                [, $action] = $this->inCity($world);
                $top = $world->placeCard(new _01026(), 'Deck-1', 1);
                $other = $world->placeCard(new _01026(), 'Deck-1', 1);
                $world->game->topFactionCards = [['id' => $top->Id]];

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01192_3,
                        'x',
                        $other->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in top');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'act refuses a non-Risk among the top cards' => function () {
                $world = new TestWorld();
                [, $action] = $this->inCity($world);
                $att = $world->placeCard(new _01049(), 'Deck-1', 1);
                $world->game->topFactionCards = [['id' => $att->Id], ['id' => $att->Id]];

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01192_3,
                        'x',
                        $att->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not Risk');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
