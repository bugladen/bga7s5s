<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01164;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01164;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01164_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01164';
    }

    /**
     * Hidden Corridors in hand; performer at Docks (has non-adjacent city spots in 2p).
     *
     * @return array{0:_01164,1:Action_01164,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01164(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Scout'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_01164 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer];
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction that needs a performer' => function () {
                $action = new Action_01164();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available when a city character has a non-adjacent destination' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'unavailable when the only character is at Home' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                // WHY: RiskCityAction requires a city character before non-adjacent filtering.
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'performers are only city characters with a non-adjacent destination' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                // Forum in 2p: adjacent Docks+Bazaar (+Home filtered by getNonAdjacent).
                // Still has Ole's / Garden as non-adjacent — keep asserting Docks performer is listed.
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'Docks performer');
            },

            'trigger queues transition 01164' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01164', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'args list non-adjacent city locations for the performer' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01164,
                    'highDramaPlayerTurn_01164'
                );

                // 2p from Docks: adjacent Forum (+Home in nonAdjacent calc) → non-adj includes Bazaar.
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $args['locationIds'], true), 'Bazaar');
                Assert::false(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'Forum adjacent');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'self excluded');
            },

            'act queues move (no engage) and ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01164,
                    'highDramaPlayerTurn_01164',
                    [Game::LOCATION_CITY_BAZAAR]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($performer->Id, $moves[0]->cardId, 'performer');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $moves[0]->toLocation, 'Bazaar');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'act refuses an adjacent location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01164,
                        'x',
                        [Game::LOCATION_CITY_FORUM]
                    );
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Forum adjacent refused');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'state constant registered' => function () {
                Assert::same(401164, States::HIGH_DRAMA_PLAYER_TURN_01164, '01164');
            },
        ];
    }
}
