<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01046;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01046a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01046a_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01046a';
    }

    private function equipGift(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $gift = $world->placeCard(new _01046(), Game::LOCATION_CITY_DOCKS, 1);
        $gift->AttachedToId = $host->Id;
        $gift->Engaged = false;
        $host->Attachments[] = $gift->Id;
        return [$host, $gift];
    }

    public function tests(): array
    {
        return [
            'available when Dark Gift ready and attached' => function () {
                $world = new TestWorld();
                [, $gift] = $this->equipGift($world);
                /** @var Action_01046a $action */
                $action = $gift->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Engaged' => function () {
                $world = new TestWorld();
                [, $gift] = $this->equipGift($world);
                $gift->Engaged = true;
                /** @var Action_01046a $action */
                $action = $gift->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'trigger queues transition 01046a' => function () {
                $world = new TestWorld();
                [, $gift] = $this->equipGift($world);
                /** @var Action_01046a $action */
                $action = $gift->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01046a', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'act engages gift, wounds host, moves to adjacent' => function () {
                $world = new TestWorld();
                [$host, $gift] = $this->equipGift($world);
                /** @var Action_01046a $action */
                $action = $gift->getActions()[0];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01046a,
                    'highDramaPhase01046a',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage gift');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wound');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($host->Id, $moves[0]->cardId, 'host');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'forum');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
