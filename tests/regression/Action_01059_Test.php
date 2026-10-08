<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01059_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01059';
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction' => function () {
                $risk = new _01059();
                Assert::instanceOf(RiskCityAction::class, $risk->getActions()[0], 'RiskCityAction');
            },

            'available with own character in the city' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01059(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Action_01059 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when only character is at Home' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01059(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_PLAYER_HOME, 1);

                /** @var Action_01059 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home only');
            },

            'trigger queues transition 01059' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01059(), Game::LOCATION_HAND, 1);
                /** @var Action_01059 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01059', $transitions[0]->transition, 'transition');
            },

            // WHY: includeHome=false — "adjacent City location" must not offer Player Home.
            'args offer adjacent city locations without Home' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01059(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01059 $action */
                $action = $risk->getActions()[0];
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01059, 'highDramaPhase01059');
                Assert::same($performer->Id, $args['performerId'], 'performerId');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'Forum only (2-player Docks)');
            },

            'picking adjacent city moves performer and resolves action' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01059(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01059 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01059,
                    'highDramaPhase01059',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($performer->Id, $moves[0]->cardId, 'performer moved');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(1, $world->game->gamestate->transitions, 'nextState');
            },

            'non-adjacent location is rejected' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01059(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01059 $action */
                $action = $risk->getActions()[0];
                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01059,
                        'highDramaPhase01059',
                        [Game::LOCATION_CITY_BAZAAR]
                    );
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not adjacent');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
