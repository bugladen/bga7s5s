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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01138;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01138;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01138_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01138';
    }

    /**
     * Performer at Docks; adjacent foe at Forum (2p map).
     *
     * @return array{0:_01138,1:Action_01138,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01138(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Action_01138 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe];
    }

    private function act(TestWorld $world, Action_01138 $action, int $state, int $id): void
    {
        $action->actFromActionWithId($world->game, $state, 'highDramaPlayerTurn_01138', $id);
    }

    private function actThrows(TestWorld $world, Action_01138 $action, int $state, int $id): bool
    {
        try {
            $this->act($world, $action, $state, $id);
        } catch (UserException | \BgaUserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            'is a RiskAction that targets characters' => function () {
                $action = new Action_01138();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
            },

            'available when a city performer has an adjacent enemy' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'adjacent foe');
            },

            'unavailable when the only enemy is at the same location' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'same location');
            },

            'unavailable when the only enemy is not adjacent' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_BAZAAR;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not adjacent');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            // WHY (2p map): Bazaar↔Forum are adjacent — a "lonely" at Bazaar still sees a Forum foe.
            // Use Bazaar + foe at Docks so only a Forum performer has an adjacent enemy.
            'performers are characters with an adjacent enemy' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01138(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $lonely = $world->placeCharacter(new GenericCharacter('Lonely'), Game::LOCATION_CITY_BAZAAR, 1);
                /** @var Action_01138 $action */
                $action = $risk->getActions()[0];

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($performer->Id, $ids, true), 'Forum sees Docks foe');
                Assert::false(in_array($lonely->Id, $ids, true), 'Bazaar has no adjacent foe');
            },

            'trigger queues the 01138 chooser' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01138', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
            },

            'args list adjacent enemy ids for the chosen performer' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_BAZAAR, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01138,
                    'highDramaPlayerTurn_01138'
                );

                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::true(in_array($foe->Id, $args['ids'], true), 'adjacent');
                Assert::false(in_array($far->Id, $args['ids'], true), 'far excluded');
            },

            // WHY: already-engaged performer cannot choose engage — auto wound path.
            'choosing a target while engaged moves, wounds, and resolves via wound transition' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $foe] = $this->scene($world);
                $performer->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $this->act($world, $action, States::HIGH_DRAMA_PLAYER_TURN_01138, $foe->Id);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($performer->Id, $moves[0]->cardId, 'performer');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to foe');
                Assert::false($moves[0]->engage, 'no auto-engage');

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['wound'], $world->game->gamestate->transitions, 'wound');
            },

            'choosing a target while en garde stores CHOSEN_TARGET and goes to manipulate' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $this->act($world, $action, States::HIGH_DRAMA_PLAYER_TURN_01138, $foe->Id);

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
                Assert::same(['manipulate'], $world->game->gamestate->transitions, 'manipulate');
            },

            'manipulate engage moves performer, engages, and sends foe Home' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $this->act($world, $action, States::HIGH_DRAMA_PLAYER_TURN_01138_2, 1);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(2, $moves, 'performer + foe');
                Assert::same($performer->Id, $moves[0]->cardId, 'performer first');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to foe loc');
                Assert::same($foe->Id, $moves[1]->cardId, 'foe Home');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[1]->toLocation, 'Home');

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($performer->Id, $engages[0]->cardId, 'performer');
                Assert::same($risk->Id, $engages[0]->sourceId, 'source');

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'manipulate do-not-engage moves performer and wounds foe' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $this->act($world, $action, States::HIGH_DRAMA_PLAYER_TURN_01138_2, 0);

                Assert::count(1, $world->theah->queuedOfType(EventCardMoving::class), 'performer move');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'choosing an invalid target throws' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_BAZAAR, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                Assert::true(
                    $this->actThrows($world, $action, States::HIGH_DRAMA_PLAYER_TURN_01138, $ally->Id),
                    'own character'
                );
                Assert::true(
                    $this->actThrows($world, $action, States::HIGH_DRAMA_PLAYER_TURN_01138, $far->Id),
                    'not adjacent'
                );
            },
        ];
    }
}
