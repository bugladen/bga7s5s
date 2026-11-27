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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
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

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no choice beside pick');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_TARGET), 'target not stored before survive');

                // Cost: performer wound is queued at pick and is NOT in the targeting batch.
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'performer wound cost');
                Assert::same($performer->Id, $wounds[0]->characterId, 'wound');
                Assert::true($wounds[0]->batchId !== $targeted[0]->batchId, 'cost outside targeting batch');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'choice');
                Assert::same('01034_2', $transitions[0]->transition, 'choice');
                Assert::same($hook->batchId, $transitions[0]->batchId, 'shared batch');
            },

            // WHY: Cloud Bottle / UL cancel the hook — no choice, no stale CHOSEN_TARGET.
            'canceled CharacterTargeted does not open the choice' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01034(), Game::LOCATION_HAND, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01034 $action */
                $action = $risk->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->playerId = 1;
                $hook->targetId = $foe->Id;
                $hook->abilityId = $action->Id;
                $hook->batchId = 9;
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_TARGET), 'no target stored');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01034(), Game::LOCATION_HAND, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01034 $action */
                $action = $risk->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = 'someone-else';
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
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
