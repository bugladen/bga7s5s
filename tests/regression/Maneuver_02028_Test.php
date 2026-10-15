<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02028;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class Maneuver_02028_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02028';
    }

    /**
     * @return array{0:_02028,1:Maneuver_02028,2:GenericCharacter}
     */
    private function duel(TestWorld $world, array $actorTraits = [], int $influence = 2): array
    {
        $risk = $world->placeCard(new _02028(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(
            new GenericCharacter('Actor', $actorTraits),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $actor->ModifiedInfluence = $influence;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_02028 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor];
    }

    public function tests(): array
    {
        return [
            // Calc: +X Thrust where X = actor ModifiedInfluence.
            'duel calc adds Thrust equal to actor ModifiedInfluence' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world, [], 3);

                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = $maneuver->Id;
                $event->actorId = $actor->Id;
                $event->thrust = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(3, $event->thrust, '+3');
                Assert::count(1, $event->explanations, 'explained');
            },

            'duel calc with zero Influence adds +0 Thrust' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world, [], 0);

                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = $maneuver->Id;
                $event->actorId = $actor->Id;
                $event->thrust = 1;
                $event->explanations = [];
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(1, $event->thrust, 'unchanged base +0');
            },

            // Resolve: if actor Diplomat → GainLethalEvent.
            'resolve as Diplomat queues GainLethal' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, ['Diplomat'], 2);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $threat = $world->theah->queuedOfType(EventThreatModified::class);
                Assert::count(1, $threat, 'lethal');
                Assert::same(null, $threat[0]->challengerThreatIsLethal, 'challenger untouched');
                Assert::same(true, $threat[0]->defenderThreatIsLethal, 'defender lethal');
            },

            'resolve as non-Diplomat does not gain Lethal' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, [], 2);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'no lethal');
            },
        ];
    }
}
