<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01019;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01019_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01019';
    }

    public function tests(): array
    {
        return [
            'available in city with another character at location' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable alone in city' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'isValidTargetForAbility rejects self and off-location' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                $local = $world->placeCharacter(new GenericCharacter('Local'), Game::LOCATION_CITY_DOCKS, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                Assert::false($action->isValidTargetForAbility($world->game, $buratino)[0], 'self');
                Assert::true($action->isValidTargetForAbility($world->game, $local)[0], 'local');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'far');
            },

            'trigger queues transition 01019' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01019', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            // WHY: pick queues ONLY CharacterTargeted so Cloud Bottle / UL / Maryam can cancel
            // before Buratino is destroyed (same seat as Action_01015 / Action_01160).
            'pick queues only CharacterTargeted' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01019,
                    'highDramaPhase01019',
                    $foe->Id
                );

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($buratino->Id, $targeted[0]->sourceId, 'source');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterDestroyed::class), 'no destroy yet');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
                Assert::contains('targets ${target_inject_code}', $world->game->notify->messages[0]['message'], 'log names the target');
                Assert::same($foe->getInjectCode(), $world->game->notify->messages[0]['args']['target_inject_code'], 'target inject');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            'surviving CharacterTargeted destroys Buratino, wounds target, resolves' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $targeted->sourceId = $buratino->Id;
                $targeted->batchId = 77;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                $destroys = $world->theah->queuedOfType(EventCharacterDestroyed::class);
                Assert::same($buratino->Id, $destroys[0]->characterId, 'destroy');
                Assert::same(77, $destroys[0]->batchId, 'destroy shares batch');
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::same($foe->Id, $wounds[0]->characterId, 'wound');
                Assert::same($action->Id, $wounds[0]->abilityId, 'ability');
                Assert::same(77, $wounds[0]->batchId, 'wound shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'canceled CharacterTargeted queues nothing (Buratino survives)' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $targeted->canceled = true;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                Assert::count(0, $world->theah->queuedEvents, 'no destroy / wound / resolve');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01019 $action */
                $action = $buratino->getActions()[0];
                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = 'other';
                $targeted->targetId = $foe->Id;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
