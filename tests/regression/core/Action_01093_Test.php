<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01093;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01093;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01093_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01093';
    }

    /**
     * Maya (P1) at the given location; P1 is first player unless $first is false.
     *
     * @return array{0:_01093,1:Action_01093}
     */
    private function scene(TestWorld $world, string $at = Game::LOCATION_CITY_DOCKS, bool $first = true): array
    {
        $maya = $world->placeCharacter(new _01093(), $at, 1);
        $world->game->globals->set(Game::FIRST_PLAYER, $first ? 1 : 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $maya->Id);
        /** @var Action_01093 $action */
        $action = $maya->getActions()[0];
        return [$maya, $action];
    }

    private function options(TestWorld $world, Action_01093 $action): array
    {
        $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01093, 'highDramaPlayerTurn_01093');
        return $args['locationIds'];
    }

    private function move(TestWorld $world, Action_01093 $action, string $to): void
    {
        $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01093, 'x', [$to]);
    }

    private function refused(TestWorld $world, Action_01093 $action, string $to): bool
    {
        try {
            $this->move($world, $action, $to);
        } catch (\BgaUserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            'available in the normal case' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable to the opponent' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'not controller');
            },

            'unavailable when Fate\'s Silence blanks Maya' => function () {
                $world = new TestWorld();
                [$maya, $action] = $this->scene($world);
                $maya->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger queues transition 01093 for the acting player' => function () {
                $world = new TestWorld();
                [$maya, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01093', $transitions[0]->transition, 'name');
                Assert::same($maya->Id, $transitions[0]->sourceId, 'source');
                Assert::same(1, $transitions[0]->playerId, 'player');
            },

            // WHY (journal 2026-04-09-12 bug 1): Home is not a City location, so the first player may not step Home.
            'first player: only adjacent city locations, never Home' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_FORUM);

                $options = $this->options($world, $action);

                Assert::same([Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR], $options, 'adjacent to Forum');
                Assert::false(in_array(Game::LOCATION_PLAYER_HOME, $options, true), 'no Home');
            },

            'first player at the Docks: only the Forum in a two-player game' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS);
                Assert::same([Game::LOCATION_CITY_FORUM], $this->options($world, $action), 'adjacent to Docks');
            },

            'not first player: every other city location plus Home' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS, false);

                $options = $this->options($world, $action);

                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $options, true), 'not her own location');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $options, true), 'Forum');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $options, true), 'Bazaar (not adjacent)');
                Assert::true(in_array(Game::LOCATION_CITY_OLES_INN, $options, true), 'Ole\'s Inn');
                Assert::true(in_array(Game::LOCATION_CITY_GOVERNORS_GARDEN, $options, true), 'Governor\'s Garden');
                Assert::true(in_array(Game::LOCATION_PLAYER_HOME, $options, true), 'Home');
                Assert::count(5, $options, 'four other city locations + Home');
            },

            // WHY (journal 2026-04-09-12 bug 4): no dead "Home" option when she is already Home.
            'not first player at Home: no Home option, all city locations' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_PLAYER_HOME, false);

                $options = $this->options($world, $action);

                Assert::false(in_array(Game::LOCATION_PLAYER_HOME, $options, true), 'no Home');
                Assert::count(5, $options, 'all five city locations');
            },

            // WHY: Lorenzo's override must make the first player resolve as "not first" in args AND in the server check.
            'override makes the first player see the any-location list' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS, true);
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);

                $options = $this->options($world, $action);

                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $options, true), 'non-adjacent offered');
                Assert::true(in_array(Game::LOCATION_PLAYER_HOME, $options, true), 'Home offered');
            },

            'override flag set to false keeps the first player adjacent-only' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS, true);
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, false);

                Assert::same([Game::LOCATION_CITY_FORUM], $this->options($world, $action), 'adjacent only');
            },

            'first player may move to an adjacent location' => function () {
                $world = new TestWorld();
                [$maya, $action] = $this->scene($world);

                $this->move($world, $action, Game::LOCATION_CITY_FORUM);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'one move');
                Assert::same($maya->Id, $moves[0]->cardId, 'Maya moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to');
                Assert::false($moves[0]->engage, 'ability move does not engage');
                Assert::same($maya->Id, $moves[0]->sourceId, 'source');
                Assert::same($action->Id, $moves[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            'first player cannot move to a non-adjacent location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                Assert::true($this->refused($world, $action, Game::LOCATION_CITY_BAZAAR), 'refused');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            // WHY (journal 2026-04-09-12 bug 1): Home is refused for the first player server-side as well.
            'first player cannot move Home' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($this->refused($world, $action, Game::LOCATION_PLAYER_HOME), 'Home refused');
            },

            'cannot move to the location she is already at' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS, false);
                Assert::true($this->refused($world, $action, Game::LOCATION_CITY_DOCKS), 'same location');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'not first player may move to any city location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS, false);

                $this->move($world, $action, Game::LOCATION_CITY_BAZAAR);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::same(Game::LOCATION_CITY_BAZAAR, $moves[0]->toLocation, 'non-adjacent allowed');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            // WHY (journal 2026-04-09-12 bug 3): Home is not a CityLocation; it must be validated by name, not fetched.
            'not first player may move Home without crashing' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS, false);

                $this->move($world, $action, Game::LOCATION_PLAYER_HOME);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
            },

            'not first player cannot move to a made-up location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS, false);
                Assert::true($this->refused($world, $action, 'Atlantis'), 'invalid');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            // WHY (journal 2026-04-09-12 bug 2): server check must honour the override, matching what args offered.
            'override lets the first player move to a non-adjacent location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS, true);
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);

                $this->move($world, $action, Game::LOCATION_CITY_BAZAAR);

                Assert::count(1, $world->theah->queuedOfType(EventCardMoving::class), 'moved');
            },

            'override lets the first player move Home' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS, true);
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);

                $this->move($world, $action, Game::LOCATION_PLAYER_HOME);

                Assert::count(1, $world->theah->queuedOfType(EventCardMoving::class), 'moved Home');
            },

            'act for another state does nothing' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01092, 'x', [Game::LOCATION_CITY_FORUM]);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'args for another state add no location list' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01092, 'x');

                Assert::false(isset($args['locationIds']), 'no locationIds');
            },
        ];
    }
}
