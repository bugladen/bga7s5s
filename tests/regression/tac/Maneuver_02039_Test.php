<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02039;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_02039_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02039';
    }

    /**
     * @return array{0:_02039,1:Maneuver_02039}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02039(), Game::LOCATION_HAND, 1);
        /** @var Maneuver_02039 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver];
    }

    public function tests(): array
    {
        return [
            'starts inactive' => function () {
                $maneuver = new Maneuver_02039();
                Assert::false($maneuver->IsActive, 'inactive');
            },

            'resolve marks the maneuver active' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::true($maneuver->IsActive, 'active');
                Assert::same(0, (int) $world->game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0), 'not yet');
            },

            'cancel clears the active flag before end of round' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);
                $maneuver->IsActive = true;

                $event = new EventManeuverCanceled();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::false($maneuver->IsActive, 'cleared');
            },

            // WHY: Threat is banked for the NEXT round via pending globals, not applied now.
            'end of round banks +1 threat for both participants next round' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);
                $maneuver->IsActive = true;
                $world->game->globals->set(Game::PENDING_CHALLENGER_THREAT, 2);
                $world->game->globals->set(Game::PENDING_DEFENDER_THREAT, 1);

                $event = new EventDuelEndOfRound();
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::false($maneuver->IsActive, 'spent');
                Assert::same(3, $world->game->globals->get(Game::PENDING_CHALLENGER_THREAT), 'challenger');
                Assert::same(2, $world->game->globals->get(Game::PENDING_DEFENDER_THREAT), 'defender');
            },

            'end of round does nothing while inactive' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);

                $event = new EventDuelEndOfRound();
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(0, (int) $world->game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0), 'challenger');
                Assert::same(0, (int) $world->game->globals->get(Game::PENDING_DEFENDER_THREAT, 0), 'defender');
            },

            'ignores resolve or cancel of a different maneuver' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = 'other';
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);
                Assert::false($maneuver->IsActive, 'not this maneuver');

                $maneuver->IsActive = true;
                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = 'other';
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);
                Assert::true($maneuver->IsActive, 'untouched');
            },
        ];
    }
}
