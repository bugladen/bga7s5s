<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01060;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01060_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01060';
    }

    private function act(TestWorld $world, Action_01060 $action, int $state, string $name, array $ids): void
    {
        $action->actFromActionWithIds($world->game, $state, $name, $ids);
    }

    private function throws(callable $fn): bool
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            'available while in hand' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'in hand');
            },

            'unavailable when not in hand' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_CITY_DISCARD, 1);
                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'discarded');
            },

            'trigger queues transition 01060' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01060', $transitions[0]->transition, 'transition');
            },

            // --- State 1: choose from-location -----------------------------------------------
            'state 1 args list locations where the player has characters' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Theirs'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01060, 'highDramaPhase01060');
                Assert::true(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'Docks offered');
                Assert::false(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'opposing-only Forum not offered');
            },

            'state 1 stores chosen location and advances' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];

                $this->act($world, $action, States::HIGH_DRAMA_PLAYER_TURN_01060, 'highDramaPhase01060', [Game::LOCATION_CITY_DOCKS]);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->game->globals->get(Game::CHOSEN_LOCATION), 'location stored');
                Assert::same([null], $world->game->gamestate->transitions, 'default nextState');
            },

            'state 1 rejects an empty location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];

                Assert::true($this->throws(fn() => $this->act(
                    $world,
                    $action,
                    States::HIGH_DRAMA_PLAYER_TURN_01060,
                    'highDramaPhase01060',
                    [Game::LOCATION_CITY_DOCKS]
                )), 'no performers');
                Assert::count(0, $world->game->gamestate->transitions, 'no transition');
            },

            // --- State 2: choose performers --------------------------------------------------
            'state 2 args list only own characters at chosen location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01060_2, 'highDramaPhase01060_2');
                Assert::same([$a->Id, $b->Id], $args['characterIds'], 'own characters at Docks');
            },

            'state 2 stores performers as JSON and transitions performersChosen' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];

                $this->act($world, $action, States::HIGH_DRAMA_PLAYER_TURN_01060_2, 'highDramaPhase01060_2', [$a->Id, $b->Id]);
                Assert::same([$a->Id, $b->Id], json_decode($world->game->globals->get(Game::CHOSEN_PERFORMER), true), 'performers');
                Assert::same(['performersChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'state 2 rejects opposing character and wrong location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $away = $world->placeCharacter(new GenericCharacter('Away'), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];

                foreach ([$foe, $away] as $bad) {
                    Assert::true($this->throws(fn() => $this->act(
                        $world,
                        $action,
                        States::HIGH_DRAMA_PLAYER_TURN_01060_2,
                        'highDramaPhase01060_2',
                        [$bad->Id]
                    )), 'rejects ' . $bad->Name);
                }
                Assert::count(0, $world->game->gamestate->transitions, 'no transition');
            },

            // WHY: Card text says "up to two", but PHP (state 2) never counts $ids. Only the client
            // (numberOfCardsSelectable = 2 in OnEnteringState.7s5s.js) caps it at 2. This test pins the
            // current server behavior so a future server-side cap is a deliberate change, not an accident.
            'state 2 does NOT enforce max two performers server-side (client-only cap)' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 1);
                $c = $world->placeCharacter(new GenericCharacter('C'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];

                $this->act($world, $action, States::HIGH_DRAMA_PLAYER_TURN_01060_2, 'highDramaPhase01060_2', [$a->Id, $b->Id, $c->Id]);
                Assert::count(3, json_decode($world->game->globals->get(Game::CHOSEN_PERFORMER), true), 'three accepted');
            },

            // --- State 3: choose destination -------------------------------------------------
            'state 3 args offer adjacent destinations and carry performers' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, json_encode([$a->Id]));

                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01060_3, 'highDramaPhase01060_3');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'Forum offered');
                Assert::false(in_array(Game::LOCATION_CITY_BAZAAR, $args['locationIds'], true), 'Bazaar not adjacent to Docks');
                Assert::same([$a->Id], $args['characterIds'], 'performers');
            },

            'state 3 moves every chosen performer together and resolves action' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, json_encode([$a->Id, $b->Id]));
                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];

                $this->act($world, $action, States::HIGH_DRAMA_PLAYER_TURN_01060_3, 'highDramaPhase01060_3', [Game::LOCATION_CITY_FORUM]);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(2, $moves, 'two moves');
                Assert::same([$a->Id, $b->Id], array_map(fn($m) => $m->cardId, $moves), 'both performers');
                foreach ($moves as $move) {
                    Assert::same(Game::LOCATION_CITY_DOCKS, $move->fromLocation, 'from');
                    Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'to');
                    Assert::false($move->engage, 'no engage');
                    Assert::same($risk->Id, $move->sourceId, 'source');
                }
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved once');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'state 3 rejects non-adjacent destination' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01060(), Game::LOCATION_HAND, 1);
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, json_encode([$a->Id]));
                /** @var Action_01060 $action */
                $action = $risk->getActions()[0];

                Assert::true($this->throws(fn() => $this->act(
                    $world,
                    $action,
                    States::HIGH_DRAMA_PLAYER_TURN_01060_3,
                    'highDramaPhase01060_3',
                    [Game::LOCATION_CITY_BAZAAR]
                )), 'not adjacent');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
