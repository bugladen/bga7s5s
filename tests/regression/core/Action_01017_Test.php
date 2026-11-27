<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01017;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01017_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01017';
    }

    public function tests(): array
    {
        return [
            'available in city with another character at location' => function () {
                $world = new TestWorld();
                $alcee = $world->placeCharacter(new _01017(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01017 $action */
                $action = $alcee->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable alone in city or at Home' => function () {
                $world = new TestWorld();
                $alcee = $world->placeCharacter(new _01017(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01017 $action */
                $action = $alcee->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'alone');

                $alcee->Location = Game::LOCATION_PLAYER_HOME;
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_PLAYER_HOME, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'at Home');
            },

            'trigger queues transition 01017' => function () {
                $world = new TestWorld();
                $alcee = $world->placeCharacter(new _01017(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01017 $action */
                $action = $alcee->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01017', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'act destroys Alcee and engages target' => function () {
                $world = new TestWorld();
                $alcee = $world->placeCharacter(new _01017(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01017 $action */
                $action = $alcee->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01017,
                    'highDramaPhase01017',
                    $foe->Id
                );

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterDestroyed::class), 'no destroy beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolved beside pick');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'nextState');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                $destroys = $world->theah->queuedOfType(EventCharacterDestroyed::class);
                Assert::same($alcee->Id, $destroys[0]->characterId, 'destroy');
                Assert::same($hook->batchId, $destroys[0]->batchId, 'destroy shares batch');
                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::same($foe->Id, $engages[0]->cardId, 'engage');
                Assert::same($hook->batchId, $engages[0]->batchId, 'engage shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            // WHY: Cloud Bottle / UL cancel the hook — Alcee must NOT be destroyed for nothing.
            'canceled CharacterTargeted does not destroy, engage or resolve' => function () {
                $world = new TestWorld();
                $alcee = $world->placeCharacter(new _01017(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_01017 $action */
                $action = $alcee->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = $action->Id;
                $hook->batchId = 9;
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                $alcee = $world->placeCharacter(new _01017(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_01017 $action */
                $action = $alcee->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = 'someone-else';
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
