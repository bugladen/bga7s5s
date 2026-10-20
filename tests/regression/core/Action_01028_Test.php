<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01028;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01028_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01028';
    }

    public function tests(): array
    {
        return [
            'trigger queues transition 01028' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01028(), Game::LOCATION_HAND, 1);
                /** @var Action_01028 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01028', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'choosing location stores CHOSEN_LOCATION' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01028(), Game::LOCATION_HAND, 1);
                /** @var Action_01028 $action */
                $action = $risk->getActions()[0];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01028,
                    'highDramaPhase01028',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::same(Game::LOCATION_CITY_FORUM, $world->game->globals->get(Game::CHOSEN_LOCATION), 'location');
            },

            'args list adjacent own Thugs for move step' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01028(), Game::LOCATION_HAND, 1);
                $thug = $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(
                    new GenericCharacter('Not Thug'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(
                    new GenericCharacter('Far Thug', ['Thug']),
                    Game::LOCATION_CITY_OLES_INN,
                    1
                );
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                /** @var Action_01028 $action */
                $action = $risk->getActions()[0];
                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01028_2,
                    'highDramaPhase01028_2'
                );

                Assert::same([$thug->Id], $args['ids'], 'only adjacent docks thug');
            },

            'moving thugs queues moves, pressure bonus, and low-priority pressure' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01028(), Game::LOCATION_HAND, 1);
                $thug = $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                /** @var Action_01028 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01028_2,
                    'highDramaPhase01028_2',
                    [$thug->Id]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to forum');
                Assert::same(1, $world->game->globals->get(Game::PRESSURE_BONUS), 'bonus');
                Assert::true(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::PACK_TACTICS_PRESSURE_TYPE),
                    'pack tactics flag'
                );
                $pressure = $world->theah->queuedOfType(EventPressureOccuring::class)[0];
                Assert::same(Event::LOW_PRIORITY, $pressure->priority, 'low priority after CardMoved');
                Assert::same('pressureLocation', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pressure');
                Assert::same(['thugsChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            'successful pressure claims when location can be claimed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01028(), Game::LOCATION_HAND, 1);
                /** @var Action_01028 $action */
                $action = $risk->getActions()[0];

                $event = new EventLocationPressureResult();
                $event->abilityId = $action->Id;
                $event->success = true;
                $event->playerId = 1;
                $event->performerId = 0;
                $event->location = Game::LOCATION_CITY_FORUM;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventLocationClaimed::class), 'claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'failed pressure still resolves action' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01028(), Game::LOCATION_HAND, 1);
                /** @var Action_01028 $action */
                $action = $risk->getActions()[0];

                $event = new EventLocationPressureResult();
                $event->abilityId = $action->Id;
                $event->success = false;
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_FORUM;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
