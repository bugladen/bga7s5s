<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01029;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01029_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01029';
    }

    public function tests(): array
    {
        return [
            'available when you control location with opposing en garde character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01029(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                /** @var Action_01029 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when you do not control the location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01029(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01029 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no control');
            },

            'unavailable when only opposing character is already engaged' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01029(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                /** @var Action_01029 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'foe engaged');
            },

            'trigger queues transition 01029' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01029(), Game::LOCATION_HAND, 1);
                /** @var Action_01029 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01029', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'isValidTargetForAbility requires opposing character at controlled location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01029(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01029 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'foe ok');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'wrong loc');
                Assert::false($action->isValidTargetForAbility($world->game, $performer)[0], 'self');
            },

            'act engages target and resolves' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01029(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01029 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01029,
                    'highDramaPhase01029',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
