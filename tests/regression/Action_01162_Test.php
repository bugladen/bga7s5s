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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01162;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01162;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01162_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01162';
    }

    /**
     * Come Hither in hand; a character in play.
     *
     * @return array{0:_01162,1:Action_01162,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01162(), Game::LOCATION_HAND, 1);
        $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_01162 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $target];
    }

    public function tests(): array
    {
        return [
            'targets characters and does not require a performer' => function () {
                $action = new Action_01162();
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::false($action->RequiresPerformerSelected, 'no performer');
            },

            'available when any character is in play' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable with no characters in play' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01162(), Game::LOCATION_HAND, 1);
                /** @var Action_01162 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty board');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'trigger queues transition 01162' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01162', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'args list every character in play' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world);
                $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_FORUM, 1);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01162,
                    'highDramaPlayerTurn_01162'
                );

                Assert::true(in_array($target->Id, $args['ids'], true), 'foe');
                Assert::true(in_array($mine->Id, $args['ids'], true), 'own');
            },

            // WHY (journal 2026-05-22 + production comment): act queues ONLY CharacterTargeted.
            // Location pick waits for surviving EventCharacterTargeted so Unyielding Loyalty /
            // Maryam / Vittoria can cancel before 01162_2 enters the queue.
            'act queues only CharacterTargeted (cancel gate), not the move' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01162,
                    'highDramaPlayerTurn_01162',
                    $target->Id
                );

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($target->Id, $targeted[0]->targetId, 'target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::same($target->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'chosen');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move yet');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no 01162_2 yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'surviving CharacterTargeted queues 01162_2 location pick' => function () {
                $world = new TestWorld();
                [$risk, $action, $target] = $this->scene($world);

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $target->Id;
                $targeted->sourceId = $risk->Id;
                $targeted->canceled = false;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                Assert::same($target->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'chosen');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01162_2', $transitions[0]->transition, 'location pick');
            },

            'canceled CharacterTargeted queues no location pick' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world);

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $target->Id;
                $targeted->canceled = true;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                Assert::count(0, $world->theah->queuedEvents, 'UL cancel holds 01162_2');
            },

            'location args are adjacent city locations for the target' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $target->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01162_2,
                    'highDramaPlayerTurn_01162_2'
                );

                Assert::same($target->Id, $args['targetId'], 'targetId');
                // 2p Docks adjacent (no Home): Forum only.
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'adjacent');
            },

            'act location queues move (no engage) and ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $target] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $target->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01162_2,
                    'highDramaPlayerTurn_01162_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($target->Id, $moves[0]->cardId, 'target');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'act location refuses a non-adjacent city' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $target->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01162_2,
                        'x',
                        [Game::LOCATION_CITY_BAZAAR]
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Bazaar not adjacent to Docks in 2p');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'state constants registered' => function () {
                Assert::same(401162, States::HIGH_DRAMA_PLAYER_TURN_01162, '01162');
                Assert::same(4011622, States::HIGH_DRAMA_PLAYER_TURN_01162_2, '01162_2');
            },
        ];
    }
}
