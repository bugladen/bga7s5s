<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01030;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01030_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01030';
    }

    public function tests(): array
    {
        return [
            'available with unengaged Sorcerer Strega facing opposition' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01030(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01030 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Strega is engaged' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01030(), Game::LOCATION_HAND, 1);
                $strega = $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $strega->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01030 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'trigger queues transition 01030' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01030(), Game::LOCATION_HAND, 1);
                /** @var Action_01030 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01030', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'act queues only CharacterTargeted (no engage / start / stage yet)' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01030(), Game::LOCATION_HAND, 1);
                $strega = $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);

                /** @var Action_01030 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01030,
                    'highDramaPhase01030',
                    $foe->Id
                );

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($risk->Id, $targeted[0]->sourceId, 'source');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage yet');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'no start yet');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no stage yet');
                Assert::same(0, (int) $world->game->globals->get(Game::CHOSEN_TARGET), 'no stale target before survive');
            },

            'surviving CharacterTargeted engages performer, starts sorcerer, queues 01030_2' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01030(), Game::LOCATION_HAND, 1);
                $strega = $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);

                /** @var Action_01030 $action */
                $action = $risk->getActions()[0];
                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $targeted->sourceId = $risk->Id;
                $targeted->batchId = 77;
                $targeted->canceled = false;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target set on survive');
                $engage = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::same($strega->Id, $engage[0]->cardId, 'engage performer');
                Assert::same(77, $engage[0]->batchId, 'engage shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'sorcerer start');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('01030_2', $transitions[0]->transition, 'next');
                Assert::same(77, $transitions[0]->batchId, 'stage shares batch');
            },

            'canceled CharacterTargeted queues nothing and leaves no target' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01030(), Game::LOCATION_HAND, 1);
                $strega = $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);

                /** @var Action_01030 $action */
                $action = $risk->getActions()[0];
                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $targeted->canceled = true;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same(0, (int) $world->game->globals->get(Game::CHOSEN_TARGET), 'no stale target');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01030(), Game::LOCATION_HAND, 1);
                /** @var Action_01030 $action */
                $action = $risk->getActions()[0];
                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = 'someone-else';
                $targeted->targetId = 1;
                $targeted->canceled = false;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            'stateFromAction queues pressure with Pull the Strand flag and sorcerer played' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01030(), Game::LOCATION_HAND, 1);
                $strega = $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                /** @var Action_01030 $action */
                $action = $risk->getActions()[0];
                $action->stateFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01030_2,
                    'highDramaPhase01030_2'
                );

                Assert::true(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::PULL_THE_STRAND_PRESSURE_TYPE),
                    'pull the strand flag'
                );
                Assert::count(1, $world->theah->queuedOfType(EventPressureOccuring::class), 'pressure');
                Assert::same('pressureLocation', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pressure transition');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'sorcerer played');
            },

            'successful pressure claims location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01030(), Game::LOCATION_HAND, 1);
                /** @var Action_01030 $action */
                $action = $risk->getActions()[0];

                $event = new EventLocationPressureResult();
                $event->abilityId = $action->Id;
                $event->success = true;
                $event->playerId = 1;
                $event->performerId = 5;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventLocationClaimed::class), 'claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
