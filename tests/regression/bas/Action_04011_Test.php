<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04011;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;

class Action_04011_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04011';
    }

    public function tests(): array
    {
        return [
            // WHY: In play and not available means a controlled Mercenary, including his own.
            // The move home does not engage.
            'sends a controlled mercenary home' => function () {
                $world = new TestWorld();
                $hans = $world->placeCharacter(new _04011(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $available = $world->placeCharacter(new GenericCharacter('Available', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 0);
                /** @var Action_04011 $action */
                $action = $hans->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'own mercenary counts');
                $merc->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'available mercenary');
                $merc->ControllerId = 2;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'opposing mercenary');
                $hans->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $hans->Location = Game::LOCATION_CITY_DOCKS;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04011, '', $merc->Id);
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($merc->Id, $targeted[0]->targetId, 'target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolved beside pick');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_PLAYER_HOME, $move->toLocation, 'home');
                Assert::false($move->engage, 'no engage');
                Assert::same($hook->batchId, $move->batchId, 'move shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same($available->Id, $available->Id, 'still here');
            },

            // WHY: Cloud Bottle / UL cancel the hook — nothing may continue.
            'canceled CharacterTargeted does not move or resolve' => function () {
                $world = new TestWorld();
                $hans = $world->placeCharacter(new _04011(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04011 $action */
                $action = $hans->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->targetId = $merc->Id;
                $hook->abilityId = $action->Id;
                $hook->batchId = 9;
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                $hans = $world->placeCharacter(new _04011(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04011 $action */
                $action = $hans->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->targetId = $merc->Id;
                $hook->abilityId = 'someone-else';
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
