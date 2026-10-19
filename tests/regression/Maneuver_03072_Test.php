<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03065;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03072;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_03072_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03072';
    }

    public function tests(): array
    {
        return [
            // WHY: Only engaged non-fake attachments on the adversary. An adversary in the
            // locker is not a target. Unengaged attachments stay.
            'destroys the adversary engaged attachments' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03072(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $engaged = $world->placeCard(new _03064(), Game::LOCATION_CITY_DOCKS, 2);
                $engaged->AttachedToId = $foe->Id;
                $engaged->Engaged = true;
                $ready = $world->placeCard(new _03065(), Game::LOCATION_CITY_DOCKS, 2);
                $ready->AttachedToId = $foe->Id;
                $foe->Attachments = [$engaged->Id, $ready->Id];
                $maneuver = $risk->getManeuvers()[0];
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'engaged attachment');
                $world->game->discardOrLockerByCardId[$foe->Id] = true;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'locker');
                $world->game->discardOrLockerByCardId[$foe->Id] = false;
                $engaged->Engaged = false;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'none engaged');
                $engaged->Engaged = true;

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::same([$engaged->Id], array_map(fn ($e) => $e->attachmentId, $world->theah->queuedOfType(EventAttachmentUnequipped::class)), 'unequip');
                Assert::same([$engaged->Id], array_map(fn ($e) => $e->cardId, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class)), 'discard');
                Assert::true($world->theah->queuedOfType(EventCardDiscardedFromPlay::class)[0]->asEffect, 'effect');
            },
        ];
    }
}
