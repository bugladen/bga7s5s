<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01087;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01087;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01087_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01087';
    }

    /** @return array{0:_01087,1:Maneuver_01087,2:Character,3:Character} */
    private function duel(TestWorld $world, array $actorTraits = []): array
    {
        $risk = $world->placeCard(new _01087(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor', $actorTraits), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;

        /** @var Maneuver_01087 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function resolve(TestWorld $world, Maneuver_01087 $maneuver, Character $foe, string $maneuverId = ''): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuverId !== '' ? $maneuverId : $maneuver->Id;
        $event->adversaryId = $foe->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'available when the round actor is not a Mercenary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'non-Mercenary');
            },

            'unavailable when the round actor is a Mercenary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, ['Mercenary']);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'Mercenary');
            },

            // WHY: unlike the Action, the Maneuver has no "must be engaged" gate — en garde on an
            // un-engaged participant is still legal text-wise. Pin it so a copy/paste of the Action gate is noticed.
            'available when the non-Mercenary actor is not engaged' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->Engaged = false;
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'no engaged gate');
            },

            'resolve en gardes the round actor on behalf of the Risk controller' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);

                $this->resolve($world, $maneuver, $foe);

                $events = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $events, 'engarde');
                Assert::same($actor->Id, $events[0]->cardId, 'participant, not adversary');
                Assert::same(1, $events[0]->playerId, 'controller');
                Assert::same($risk->Id, $events[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $events[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedEvents, 'only the engarde');
            },

            'resolve for a different maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);

                $this->resolve($world, $maneuver, $foe, 'someOtherManeuver');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
