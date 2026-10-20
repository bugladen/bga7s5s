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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03cd01_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03cd01';
    }

    /**
     * @return array{0:_03cd01,1:Action_03cd01,2:GenericCharacter}
     */
    private function ready(TestWorld $world): array
    {
        $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_CITY_DOCKS, 1);
        $friend = $world->placeCharacter(new GenericCharacter('Friend'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_03cd01 $action */
        $action = $penya->getActions()[0];
        return [$penya, $action, $friend];
    }

    public function tests(): array
    {
        return [
            'available only while he is unengaged in the city with a friendly companion' => function () {
                $world = new TestWorld();
                [$penya, $action, $friend] = $this->ready($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');

                $penya->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $penya->Engaged = false;

                $friend->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'companion left');
                $friend->Location = Game::LOCATION_CITY_DOCKS;

                $friend->ControllerId = 2;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'opposing companion');
                $friend->ControllerId = 1;

                $penya->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $penya->Location = Game::LOCATION_CITY_DOCKS;

                $penya->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');

                $action->Used = true;
                $penya->ControllerId = 1;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            'trigger opens the companion choice for the activating player' => function () {
                $world = new TestWorld();
                [$penya, $action] = $this->ready($world);

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                $transition = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transition, 'one transition');
                Assert::same('03cd01', $transition[0]->transition, 'state');
                Assert::same(1, $transition[0]->playerId, 'player');
                Assert::same($penya->Id, $transition[0]->sourceId, 'penya');
                Assert::same($action->Id, $transition[0]->internalId, 'action');
            },

            // WHY: Companion buttons follow Penya's controller, not the active player.
            // Two-player Docks adjacency is Forum only; Home is excluded.
            'args list his controller\'s companions, then adjacent city locations' => function () {
                $world = new TestWorld();
                [$penya, $action, $friend] = $this->ready($world);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->activePlayerId = 2;

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD01, '');
                Assert::same($penya->Id, $args['performerId'], 'performer');
                Assert::same([$friend->Id], $args['ids'], 'only the friend');

                $world->game->globals->set(Game::CHOSEN_TARGET, $friend->Id);
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD01_2, '');
                Assert::same($friend->Id, $args['targetId'], 'target');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'Forum only');
            },

            'choosing himself, a foe, a missing character, or someone elsewhere is rejected' => function () {
                $world = new TestWorld();
                [$penya, $action, $friend] = $this->ready($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $away = $world->placeCharacter(new GenericCharacter('Away'), Game::LOCATION_CITY_FORUM, 1);

                foreach ([$penya->Id, $foe->Id, 99999, $away->Id] as $id) {
                    $threw = false;
                    try {
                        $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD01, '', $id);
                    } catch (UserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, 'rejected ' . $id);
                }
                Assert::count(0, $world->game->gamestate->transitions, 'no transition');
            },

            'a legal companion is stored and advances' => function () {
                $world = new TestWorld();
                [, $action, $friend] = $this->ready($world);
                $world->game->activePlayerId = 2;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD01, '', $friend->Id);

                Assert::same($friend->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'stored');
                Assert::same(['companionChosen'], $world->game->gamestate->transitions, 'named transition');
            },

            'a non-adjacent location is rejected before any move' => function () {
                $world = new TestWorld();
                [$penya, $action, $friend] = $this->ready($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $friend->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_03CD01_2,
                        '',
                        [Game::LOCATION_CITY_BAZAAR]
                    );
                } catch (UserException $e) {
                    $threw = true;
                    Assert::contains($penya->Location, $e->getMessage(), 'names origin');
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            // WHY: Cost is engage Penya. Both moves are unengaged. Companion moves first.
            'a legal location engages him and moves the companion, then him' => function () {
                $world = new TestWorld();
                [$penya, $action, $friend] = $this->ready($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $friend->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_03CD01_2,
                    '',
                    [Game::LOCATION_CITY_FORUM]
                );

                $engage = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engage, 'engage');
                Assert::same($penya->Id, $engage[0]->cardId, 'penya engaged');
                Assert::same($action->Id, $engage[0]->abilityId, 'ability');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(2, $moves, 'two moves');
                Assert::same($friend->Id, $moves[0]->cardId, 'companion first');
                Assert::same($penya->Id, $moves[1]->cardId, 'penya second');
                Assert::false($moves[0]->engage, 'companion stays ready');
                Assert::false($moves[1]->engage, 'penya move does not engage again');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from docks');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[1]->toLocation, 'to forum');
                Assert::same($penya->Id, $moves[1]->sourceId, 'source is Penya');
                Assert::same($action->Id, $moves[1]->abilityId, 'ability');

                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::count(1, $resolved, 'resolved');
                Assert::same(1, $resolved[0]->playerId, 'controller');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'named transition');
            },
        ];
    }
}
