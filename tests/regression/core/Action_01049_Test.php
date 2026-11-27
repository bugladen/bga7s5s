<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01049_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01049';
    }

    private function equipFlint(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $flint = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 1);
        $flint->AttachedToId = $host->Id;
        $host->Attachments[] = $flint->Id;
        return [$host, $flint];
    }

    public function tests(): array
    {
        return [
            'available with opposing character at location' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without opposing character' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'trigger queues transition 01049' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01049', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            // WHY: Pick used to wound/engage inline — Cloud Bottle never saw a targeting hook.
            // Act queues ONLY CharacterTargeted; survive continues.
            // Already-engaged target skips choice — wound + engage flintlock immediately on survive.
            // Must still fire RangedAbilityPlayed / ActionResolved (Matchlock 01156 shape) so
            // Katain's copy window opens even if a BeingWounded cancel-first reaction runs.
            'selecting engaged target queues CharacterTargeted only; survive wounds and engages flintlock' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01049,
                    'highDramaPhase01049',
                    $foe->Id
                );

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'no ranged beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolved beside pick');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'named');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($hook->batchId, $wounds[0]->batchId, 'wound shares batch');
                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage flint');
                Assert::same($flint->Id, $engages[0]->cardId, 'flint engaged');
                Assert::same($hook->batchId, $engages[0]->batchId, 'engage shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'selecting engarde target queues CharacterTargeted only; survive queues opponent choice' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01049,
                    'highDramaPhase01049',
                    $foe->Id
                );

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no choice beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'choice');
                Assert::same('01049_2', $transitions[0]->transition, 'choice');
                Assert::same($foe->ControllerId, $transitions[0]->playerId, 'foe controller');
                Assert::same($hook->batchId, $transitions[0]->batchId, 'shared batch');
            },

            // WHY: Cloud Bottle / UL cancel the hook — nothing may continue.
            'canceled CharacterTargeted does not wound or open the choice' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->playerId = 1;
                $hook->targetId = $foe->Id;
                $hook->sourceId = $flint->Id;
                $hook->abilityId = $action->Id;
                $hook->batchId = 9;
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::count(0, $world->theah->queuedEvents, 'no continue');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_TARGET), 'no target stored');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = 'someone-else';
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
            'opponent chooses engage' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01049_2,
                    'highDramaPhase01049_2',
                    1
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage foe');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'opponent refuses — wound and engage flintlock' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01049_2,
                    'highDramaPhase01049_2',
                    2
                );

                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wound');
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage flint');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
