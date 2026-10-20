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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04cd01_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04cd01';
    }

    /**
     * @return array{0:_04cd01,1:Action_04cd01,2:GenericCharacter}
     */
    private function equipped(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $penya = $world->placeCard(new _04cd01(), Game::LOCATION_CITY_DOCKS, 1);
        $penya->AttachedToId = $host->Id;
        /** @var Action_04cd01 $action */
        $action = $penya->getActions()[0];
        return [$penya, $action, $host];
    }

    public function tests(): array
    {
        return [
            // WHY: Cost is engaging the attachment. The host may already be engaged.
            // Home has neighbors, but the printed move is a city-location move.
            'available while the attachment is unengaged on a city character' => function () {
                $world = new TestWorld();
                [$penya, $action, $host] = $this->equipped($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');

                $host->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'host may be engaged');
                $host->Engaged = false;

                $penya->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'attachment engaged');
                $penya->Engaged = false;

                $host->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $host->Location = Game::LOCATION_CITY_DOCKS;

                $penya->AttachedToId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not equipped');
                $penya->AttachedToId = $host->Id;

                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'other controller');

                // WHY: CardAction skips the controller check when ControllerId is 0.
                // This action does not add the isControlled gate that Astrid's action has.
                $penya->ControllerId = 0;
                Assert::true($action->isAvailableToPlayer(2, $world->theah), 'uncontrolled still offered');
            },

            'trigger opens the location choice for the attachment controller' => function () {
                $world = new TestWorld();
                [$penya, $action] = $this->equipped($world);

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 2;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                $transition = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transition, 'one transition');
                Assert::same('04cd01', $transition[0]->transition, 'state');
                Assert::same(1, $transition[0]->playerId, 'controller, not the triggering player');
                Assert::same($penya->Id, $transition[0]->sourceId, 'attachment');
                Assert::same($action->Id, $transition[0]->internalId, 'action');
            },

            'args are the host and adjacent city locations' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->equipped($world);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD01, '');
                Assert::same($host->Id, $args['performerId'], 'host');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'Forum only');
            },

            'a missing host or a non-adjacent location is rejected before any queue' => function () {
                $world = new TestWorld();
                [$penya, $action] = $this->equipped($world);

                $penya->AttachedToId = 0;
                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD01, '', [Game::LOCATION_CITY_FORUM]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'missing host');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                $penya->AttachedToId = $world->theah->getCharactersAtLocation(Game::LOCATION_CITY_DOCKS)[0]->Id;

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD01, '', [Game::LOCATION_PLAYER_HOME]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'home is not a destination');
                Assert::count(0, $world->theah->queuedEvents, 'still nothing queued');
            },

            // WHY: The printed cost engages this card. The move does not engage the host.
            'a legal location engages the attachment and moves the host' => function () {
                $world = new TestWorld();
                [$penya, $action, $host] = $this->equipped($world);

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD01, '', [Game::LOCATION_CITY_FORUM]);

                $engaged = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engaged, 'engage');
                Assert::same($penya->Id, $engaged[0]->cardId, 'attachment engaged');
                Assert::same($action->Id, $engaged[0]->abilityId, 'this action');

                $moving = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moving, 'move');
                Assert::same($host->Id, $moving[0]->cardId, 'host');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moving[0]->toLocation, 'to');
                Assert::false($moving[0]->engage, 'move does not engage');
                Assert::same($penya->Id, $moving[0]->sourceId, 'source');

                Assert::same(1, $world->theah->queuedOfType(EventActionResolved::class)[0]->playerId, 'resolved');
                Assert::same('locationChosen', $world->game->gamestate->transitions[0], 'transition');
            },
        ];
    }
}
