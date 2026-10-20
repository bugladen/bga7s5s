<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01046;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01046b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;

class Action_01046b_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01046b';
    }

    private function equipGift(TestWorld $world, int $wounds = 1): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $host->Wounds = $wounds;
        $gift = $world->placeCard(new _01046(), Game::LOCATION_CITY_DOCKS, 1);
        $gift->AttachedToId = $host->Id;
        $gift->Engaged = false;
        $host->Attachments[] = $gift->Id;
        return [$host, $gift];
    }

    public function tests(): array
    {
        return [
            'available when host wounded and gift ready' => function () {
                $world = new TestWorld();
                [, $gift] = $this->equipGift($world, 1);
                /** @var Action_01046b $action */
                $action = $gift->getActions()[1];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when host has no wounds' => function () {
                $world = new TestWorld();
                [, $gift] = $this->equipGift($world, 0);
                /** @var Action_01046b $action */
                $action = $gift->getActions()[1];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'healthy');
            },

            'trigger engages gift and heals host' => function () {
                $world = new TestWorld();
                [$host, $gift] = $this->equipGift($world, 2);
                /** @var Action_01046b $action */
                $action = $gift->getActions()[1];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage');
                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'heal');
                Assert::same($host->Id, $heals[0]->characterId, 'host');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
