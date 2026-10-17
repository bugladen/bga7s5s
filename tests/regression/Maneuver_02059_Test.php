<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02059;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_02059_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02059';
    }

    /**
     * @return array{0:_02059,1:Maneuver_02059,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02059(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Quick'), Game::LOCATION_CITY_DOCKS, 1);
        $actor->ModifiedFinesse = 3;
        $world->theah->duelActor = $actor;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_02059 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor];
    }

    public function tests(): array
    {
        return [
            'available in a duel when the actor has 3 Finesse or more' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), '3');
                $actor->ModifiedFinesse = 2;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), '2');
                $actor->ModifiedFinesse = 5;
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a duel');
            },

            // WHY: The Finesse gate is availability only. The value event adds Riposte whenever the id matches.
            'calculate adds +1 Riposte' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->scene($world);
                $actor->ModifiedFinesse = 1;
                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = $maneuver->Id;
                $event->riposte = 2;
                $event->parry = 0;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);
                Assert::same(3, $event->riposte, 'riposte');
                Assert::same(0, $event->parry, 'parry');
                Assert::contains($risk->getInjectCode(), $event->explanations[0], 'card');
            },

            'ignores a different maneuver id' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);
                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = 'other';
                $event->riposte = 4;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);
                Assert::same(4, $event->riposte, 'unchanged');
                Assert::count(0, $event->explanations, 'silent');
            },
        ];
    }
}
