<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02029;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_02029_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02029';
    }

    /**
     * @return array{0:_02029,1:Maneuver_02029,2:GenericCharacter}
     */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02029(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(
            new GenericCharacter('Actor', ['Diplomat']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_02029 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor];
    }

    public function tests(): array
    {
        return [
            // Calc: +1 Riposte per Diplomat you control at actor location.
            'duel calc adds +1 Riposte per controlled Diplomat at location' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->placeCharacter(
                    new GenericCharacter('Dip2', ['Diplomat']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Enemy Dip', ['Diplomat']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );

                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = $maneuver->Id;
                $event->riposte = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(2, $event->riposte, 'actor + Dip2');
                Assert::count(1, $event->explanations, 'explained');
            },

            'duel calc with no Diplomats adds nothing' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->ModifiedTraits = ['Mercenary'];

                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = $maneuver->Id;
                $event->riposte = 1;
                $event->explanations = [];
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(1, $event->riposte, 'unchanged');
                Assert::count(0, $event->explanations, 'no explain');
            },

            'ignores Diplomats at other locations' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->placeCharacter(
                    new GenericCharacter('Forum Dip', ['Diplomat']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = $maneuver->Id;
                $event->riposte = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(1, $event->riposte, 'actor only');
            },
        ];
    }
}
