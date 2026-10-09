<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01108;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01108b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01108b_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01108b';
    }

    /** @return array{0:_01108,1:Maneuver_01108b} */
    private function duel(TestWorld $world, array $actorTraits = ['Pirate']): array
    {
        $risk = $world->placeCard(new _01108(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor', $actorTraits), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;

        /** @var Maneuver_01108b $maneuver */
        $maneuver = $risk->getManeuvers()[1];
        return [$risk, $maneuver];
    }

    public function tests(): array
    {
        return [
            'available when the actor is a Pirate' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'Pirate');
            },

            'unavailable when the actor is not a Pirate' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, ['Scoundrel']);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not Pirate');
            },

            'resolve draws a card for the Risk controller' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same(1, $draws[0]->playerId, 'controller draws');
            },

            'resolve for another maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'someOtherManeuver';
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
