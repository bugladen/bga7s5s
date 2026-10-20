<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02049;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_02049_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02049';
    }

    /**
     * @return array{0:_02049,1:Maneuver_02049,2:GenericCharacter}
     */
    private function scene(TestWorld $world, array $traits = ['Mercenary']): array
    {
        $risk = $world->placeCard(new _02049(), Game::LOCATION_HAND, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Mark', $traits), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelOpponent = $adversary;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_02049 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $adversary];
    }

    public function tests(): array
    {
        return [
            'available in a duel when the adversary is a Mercenary or Thug' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world, ['Thug']);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'thug');

                $world->theah->duelOpponent = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'mercenary');
            },

            'unavailable outside a duel or against another trait' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world, ['Noble']);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'noble');

                $world->theah->duelOpponent->addTrait($world->game, 'Mercenary');
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            // WHY: The trait gate is availability. The value calc adds +1 Riposte whenever this maneuver resolves.
            'calculate adds +1 Riposte' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->scene($world);

                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = $maneuver->Id;
                $event->riposte = 2;
                $event->parry = 1;
                $event->thrust = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(3, $event->riposte, 'riposte');
                Assert::same(1, $event->parry, 'parry');
                Assert::same(0, $event->thrust, 'thrust');
                Assert::count(1, $event->explanations, 'explained');
                Assert::true(str_contains($event->explanations[0], $risk->getInjectCode()), 'card');
            },

            'ignores a different maneuver id' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);

                $event = new EventDuelCalculateManeuverValues();
                $event->maneuverId = 'other';
                $event->riposte = 4;
                $event->explanations = [];
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(4, $event->riposte, 'unchanged');
                Assert::count(0, $event->explanations, 'silent');
            },
        ];
    }
}
