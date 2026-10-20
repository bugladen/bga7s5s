<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03065;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03065;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;

class Action_03065_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03065';
    }

    public function tests(): array
    {
        return [
            'is an attachment action' => function () {
                Assert::instanceOf(AttachmentAction::class, new Action_03065(), 'attachment action');
            },

            // WHY: A stale Used flag on an equipped copy is cleared so it can be chosen.
            // Home is not a city location. The sink is the bottom of the faction deck,
            // and the move home does not engage.
            'sinks the lodestone and moves the performer home' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $stone = $world->placeCard(new _03065(), Game::LOCATION_CITY_DOCKS, 1);
                $stone->AttachedToId = $host->Id;
                $host->Attachments[] = $stone->Id;
                /** @var Action_03065 $action */
                $action = $stone->getActions()[0];
                $action->Used = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'stale used');
                Assert::false($action->Used, 'cleared');

                $host->Location = Game::LOCATION_PLAYER_HOME;
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                Assert::false($action->Used, 'still cleared');
                $host->Location = Game::LOCATION_CITY_DOCKS;

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same($stone->Id, $world->theah->queuedOfType(EventAttachmentUnequipped::class)[0]->attachmentId, 'unequip');
                Assert::same($stone->Id, $world->theah->queuedOfType(EventCardRemovedFromPlay::class)[0]->cardId, 'removed');
                $sink = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class)[0];
                Assert::same($stone->Id, $sink->cardId, 'sunk');
                Assert::false($sink->onTop, 'bottom');
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_PLAYER_HOME, $move->toLocation, 'home');
                Assert::false($move->engage, 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
