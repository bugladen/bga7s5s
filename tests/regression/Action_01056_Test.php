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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01056_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01056';
    }

    /** @return array{0:_01056,1:Action_01056,2:Character,3:Character} */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01056(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

        /** @var Action_01056 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe];
    }

    private function selectTarget(TestWorld $world, Action_01056 $action, Character $performer, Character $foe): void
    {
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01056, 'x', $foe->Id);
    }

    private function defenderChoice(TestWorld $world, Action_01056 $action, Character $foe, int $choice): void
    {
        $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
        $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01056_2, 'x', $choice);
    }

    public function tests(): array
    {
        return [
            'available with performer facing opposing character' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'performers');
            },

            'unavailable without opposing character at performer location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01056(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01056 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'trigger queues transition 01056' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01056', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'getArgs lists opposing characters at performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Pal'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01056, 'x');
                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::same([$foe->Id], $args['characterIds'], 'opposing only');
            },

            'isValidTargetForAbility rejects own and distant characters' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $pal = $world->placeCharacter(new GenericCharacter('Pal'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'foe');
                Assert::false($action->isValidTargetForAbility($world->game, $pal)[0], 'own');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'far');
            },

            'selecting target rejects an invalid character without queuing anything' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $pal = $world->placeCharacter(new GenericCharacter('Pal'), Game::LOCATION_CITY_DOCKS, 1);

                $threw = false;
                try {
                    $this->selectTarget($world, $action, $performer, $pal);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            // WHY regression: ChallengeIssued must fire BEFORE the defender may move Home so
            // "When X issues a challenge" reactions still trigger (card text: challenge is issued, then cancelled).
            'selecting target issues Combat challenge before the defender choice transition' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $foe] = $this->scene($world);

                $this->selectTarget($world, $action, $performer, $foe);

                Assert::same(Game::MOVE_ALONG_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'Combat challenge');
                Assert::same(false, $world->game->globals->get(Game::CHALLENGE_CANCELLED), 'not cancelled yet');
                Assert::same($performer->Location, $world->game->globals->get(Game::CHOSEN_LOCATION), 'location');
                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');

                $queued = $world->theah->queuedEvents;
                Assert::count(2, $queued, 'challenge + transition');
                Assert::instanceOf(EventChallengeIssued::class, $queued[0], 'challenge first');
                Assert::instanceOf(EventTransition::class, $queued[1], 'transition second');
                Assert::same('01056_2', $queued[1]->transition, 'defender choice');
                // Defender chooses, so the transition belongs to the defender's player.
                Assert::same($foe->ControllerId, $queued[1]->playerId, 'defender player');

                Assert::same($performer->Id, $queued[0]->challengerId, 'challenger');
                Assert::same($foe->Id, $queued[0]->defenderId, 'defender');
                Assert::same($risk->Id, $queued[0]->sourceId, 'source');
                Assert::same($action->Id, $queued[0]->abilityId, 'ability');
                Assert::same([null], $world->game->gamestate->transitions, 'next state');
            },

            'defender id=1 moves Home engaged and cancels the challenge' => function () {
                $world = new TestWorld();
                [$risk, $action, , $foe] = $this->scene($world);

                $this->defenderChoice($world, $action, $foe, 1);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($foe->Id, $moves[0]->cardId, 'defender moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::true($moves[0]->engage, 'engaged');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::same(true, $world->game->globals->get(Game::CHALLENGE_CANCELLED), 'cancelled');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01056_3', $transitions[0]->transition, 'check cancelled');
            },

            'defender id=2 continues the challenge without moving' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHALLENGE_CANCELLED, false);

                $this->defenderChoice($world, $action, $foe, 2);

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
                Assert::same(false, $world->game->globals->get(Game::CHALLENGE_CANCELLED), 'still live');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01056_3', $transitions[0]->transition, 'check cancelled');
                Assert::same([null], $world->game->gamestate->transitions, 'next state');
            },
        ];
    }
}
