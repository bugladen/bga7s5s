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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01115;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01115;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01115_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01115';
    }

    /**
     * Taunt in hand; performer at Docks; foe at Forum (adjacent in 2p).
     *
     * @return array{0:_01115,1:Action_01115,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01115(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Action_01115 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe];
    }

    public function tests(): array
    {
        return [
            'targets characters' => function () {
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, new Action_01115(), 'characters');
            },

            'available with an adjacent enemy' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without an adjacent enemy' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_BAZAAR;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'far');
            },

            'unavailable when the only enemy is at the same location' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'same location');
            },

            'unavailable when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'trigger queues transition 01115' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01115', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'args list adjacent enemies only' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_BAZAAR, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01115, 'x');

                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::same([$foe->Id], $args['ids'], 'foe only');
            },

            'act moves the foe to the performer and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01115, 'x', $foe->Id);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'one move');
                Assert::same($foe->Id, $moves[0]->cardId, 'foe');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->toLocation, 'to performer');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::same($action->Id, $moves[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'act refuses own character' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01115, 'x', $ally->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
