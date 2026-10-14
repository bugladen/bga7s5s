<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02018;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_02018_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02018';
    }

    /**
     * @return array{0:_02018,1:Maneuver_02018,2:GenericCharacter}
     */
    private function duel(TestWorld $world, int $actorWounds = 0): array
    {
        $risk = $world->placeCard(new _02018(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $actor->Wounds = $actorWounds;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_02018 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor];
    }

    public function tests(): array
    {
        return [
            'resolve wounds the duel actor' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($actor->Id, $wounds[0]->characterId, 'actor');
                Assert::same(1, $wounds[0]->wounds, 'one');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
            },

            // WHY (journal 2026-03-23-01): thrust += actor->Wounds + 1 because the Resolve
            // wound is only queued — not yet applied when calc runs.
            'duel calc adds Thrust equal to current Wounds plus one' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world, 2);

                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = $maneuver->Id;
                $event->thrust = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(3, $event->thrust, '2 wounds + 1');
                Assert::same(2, $actor->Wounds, 'wounds not applied yet');
                Assert::count(1, $event->explanations, 'explained');
            },

            'duel calc with zero wounds still adds +1 Thrust' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 0);

                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = $maneuver->Id;
                $event->thrust = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(1, $event->thrust, '+1');
            },
        ];
    }
}
