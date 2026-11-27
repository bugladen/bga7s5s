<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01044;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01044_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01044';
    }

    private function setupArmed(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01044(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $att = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
        $att->Engaged = false;
        $performer->Attachments[] = $att->Id;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        return [$scheme, $performer, $att, $foe];
    }

    public function tests(): array
    {
        return [
            // WHY: performers must have an unengaged attachment AND opposing with ≤ attachments
            'available with performer who has ready attachment vs opposing' => function () {
                $world = new TestWorld();
                [$scheme] = $this->setupArmed($world);
                /** @var Action_01044 $action */
                $action = $scheme->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when only attachments are engaged' => function () {
                $world = new TestWorld();
                [$scheme, $performer, $att] = $this->setupArmed($world);
                $att->Engaged = true;
                /** @var Action_01044 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged att');
            },

            'getPerformersForAction excludes characters without ready attachment' => function () {
                $world = new TestWorld();
                [$scheme, $performer] = $this->setupArmed($world);
                $bare = $world->placeCharacter(new GenericCharacter('Bare'), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('Foe2'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01044 $action */
                $action = $scheme->getActions()[0];
                $performers = $action->getPerformersForAction(1, $world->theah);
                $ids = array_map(fn($c) => $c->Id, $performers);
                Assert::true(in_array($performer->Id, $ids, true), 'armed performer');
                Assert::false(in_array($bare->Id, $ids, true), 'bare excluded');
            },

            'trigger queues transition 01044' => function () {
                $world = new TestWorld();
                [$scheme] = $this->setupArmed($world);
                /** @var Action_01044 $action */
                $action = $scheme->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01044', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'engage choice engages attachment and target' => function () {
                $world = new TestWorld();
                [$scheme, $performer, $att, $foe] = $this->setupArmed($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $att->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                /** @var Action_01044 $action */
                $action = $scheme->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01044_3,
                    'highDramaPhase01044_3',
                    1
                );

                // Pick: attachment engage (cost) + targeting only.
                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'attachment cost only beside pick');
                Assert::same($att->Id, $engages[0]->cardId, 'attachment engaged');
                Assert::true($engages[0]->batchId === null, 'cost outside targeting batch');
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolved beside pick');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'foe engaged on survive');
                Assert::same($foe->Id, $engages[0]->cardId, 'foe');
                Assert::same($hook->batchId, $engages[0]->batchId, 'engage shares batch');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no home');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            // WHY: Cloud Bottle / UL cancel the hook — attachment engage stays paid (cost), but
            // the effect + resolve must not run, and the stashed choice must not go stale.
            'canceled CharacterTargeted skips effect and clears stashed choice' => function () {
                $world = new TestWorld();
                [$scheme, $performer, $att, $foe] = $this->setupArmed($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $att->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                /** @var Action_01044 $action */
                $action = $scheme->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01044_3,
                    'highDramaPhase01044_3',
                    2
                );

                $hook = $world->theah->queuedOfType(EventCharacterTargeted::class)[0];
                $world->theah->takeQueuedEvents();
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same(0, $world->game->globals->get('action01044Manipulation'), 'choice cleared');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                [$scheme, , , $foe] = $this->setupArmed($world);

                /** @var Action_01044 $action */
                $action = $scheme->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = 'someone-else';
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            'home choice engages attachment and moves target Home' => function () {
                $world = new TestWorld();
                [$scheme, $performer, $att, $foe] = $this->setupArmed($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $att->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                /** @var Action_01044 $action */
                $action = $scheme->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01044_3,
                    'highDramaPhase01044_3',
                    2
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'att engage cost');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no home beside pick');
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'foe not engaged');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'home');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'to home');
                Assert::same($hook->batchId, $moves[0]->batchId, 'move shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
