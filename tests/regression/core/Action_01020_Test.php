<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01020;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01020_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01020';
    }

    public function tests(): array
    {
        return [
            'available when another character is elsewhere in play' => function () {
                $world = new TestWorld();
                $dante = $world->placeCharacter(new _01020(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01020 $action */
                $action = $dante->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when only other characters share Dante location' => function () {
                $world = new TestWorld();
                $dante = $world->placeCharacter(new _01020(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Here'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01020 $action */
                $action = $dante->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'same location only');
            },

            'isValidTargetForAbility rejects same location' => function () {
                $world = new TestWorld();
                $dante = $world->placeCharacter(new _01020(), Game::LOCATION_CITY_DOCKS, 1);
                $here = $world->placeCharacter(new GenericCharacter('Here'), Game::LOCATION_CITY_DOCKS, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01020 $action */
                $action = $dante->getActions()[0];
                Assert::false($action->isValidTargetForAbility($world->game, $here)[0], 'same loc');
                Assert::true($action->isValidTargetForAbility($world->game, $far)[0], 'elsewhere');
            },

            'trigger queues transition 01020' => function () {
                $world = new TestWorld();
                $dante = $world->placeCharacter(new _01020(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01020 $action */
                $action = $dante->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01020', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'act destroys Dante and moves target to Dante location' => function () {
                $world = new TestWorld();
                $dante = $world->placeCharacter(new _01020(), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01020 $action */
                $action = $dante->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01020,
                    'highDramaPhase01020',
                    $far->Id
                );

                Assert::same($dante->Id, $world->theah->queuedOfType(EventCharacterDestroyed::class)[0]->characterId, 'destroy');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($far->Id, $moves[0]->cardId, 'target moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->toLocation, 'to Dante loc');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'nextState');
            },
        ];
    }
}
