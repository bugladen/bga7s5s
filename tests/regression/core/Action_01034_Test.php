<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01034;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01034_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01034';
    }

    public function tests(): array
    {
        return [
            // WHY regression: audit 2026-06-05 — performer Engaged + opposing en garde
            'available with engaged performer facing opposing en garde' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01034(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01034 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when performer is en garde' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01034(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01034 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'performer en garde');
            },

            'unavailable when only opposing characters are engaged' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01034(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Engaged = true;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;

                /** @var Action_01034 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no en garde foe');
            },

            'trigger queues transition 01034' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01034(), Game::LOCATION_HAND, 1);
                /** @var Action_01034 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01034', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            // WHY regression: audit 2026-06-05 — isValidTargetForAbility enforces en garde
            'isValidTargetForAbility rejects engaged target' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01034(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $engaged = $world->placeCharacter(new GenericCharacter('Engaged'), Game::LOCATION_CITY_DOCKS, 2);
                $engaged->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01034 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'en garde ok');
                Assert::false($action->isValidTargetForAbility($world->game, $engaged)[0], 'engaged rejected');
            },

            'act wounds performer and transitions to target choice' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01034(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Engaged = true;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01034 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01034,
                    'highDramaPhase01034',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wound');
                Assert::same('01034_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'choice');
            },

            'target engages when they accept' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01034(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                /** @var Action_01034 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01034_2,
                    'highDramaPhase01034_2',
                    1
                );

                Assert::same($foe->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage foe');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'pass engardes performer instead' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01034(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Engaged = true;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                /** @var Action_01034 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_01034_2);

                Assert::same($performer->Id, $world->theah->queuedOfType(EventCardEngarded::class)[0]->cardId, 'en garde');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
