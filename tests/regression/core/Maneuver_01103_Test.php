<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01103;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01103;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01103_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01103';
    }

    /** @return array{0:_01103,1:Maneuver_01103} */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01103(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01103 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver];
    }

    private function useParry(Maneuver_01103 $maneuver): bool
    {
        $prop = new \ReflectionProperty(Maneuver_01103::class, 'UseParry');
        $prop->setAccessible(true);
        return (bool)$prop->getValue($maneuver);
    }

    private function useThrust(Maneuver_01103 $maneuver): bool
    {
        $prop = new \ReflectionProperty(Maneuver_01103::class, 'UseThrust');
        $prop->setAccessible(true);
        return (bool)$prop->getValue($maneuver);
    }

    public function tests(): array
    {
        return [
            'available in a duel with Adaptable in hand' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'resolve queues high-priority transition 01103' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01103', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'internal');
            },

            'act id 1 chooses +2 Parry; id 2 chooses +2 Thrust' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01103,
                    'duelResolveManeuver_01103',
                    1
                );
                Assert::true($this->useParry($maneuver), 'parry');
                Assert::false($this->useThrust($maneuver), 'not thrust');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');

                $world->game->gamestate->transitions = [];
                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01103,
                    'duelResolveManeuver_01103',
                    2
                );
                Assert::true($this->useThrust($maneuver), 'thrust');
            },

            'calculate always adds +2 Riposte; Parry or Thrust follows the choice' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $base = new EventDuelCalculateManeuverValues();
                $base->maneuverId = $maneuver->Id;
                $base->riposte = 0;
                $base->parry = 0;
                $base->thrust = 0;
                $base->theah = $world->theah;
                $maneuver->handleEvent($base);
                Assert::same(2, $base->riposte, '+2 Riposte before choice');
                Assert::same(0, $base->parry, 'no Parry yet');
                Assert::same(0, $base->thrust, 'no Thrust yet');

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01103,
                    'x',
                    1
                );
                $parry = new EventDuelCalculateManeuverValues();
                $parry->maneuverId = $maneuver->Id;
                $parry->riposte = 0;
                $parry->parry = 0;
                $parry->thrust = 0;
                $parry->theah = $world->theah;
                $maneuver->handleEvent($parry);
                Assert::same(2, $parry->riposte, 'Riposte');
                Assert::same(2, $parry->parry, '+2 Parry');
                Assert::same(0, $parry->thrust, 'no Thrust');
            },

            'maneuver cancel clears Parry/Thrust choice' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01103,
                    'x',
                    2
                );

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);

                Assert::false($this->useParry($maneuver), 'parry cleared');
                Assert::false($this->useThrust($maneuver), 'thrust cleared');
            },

            'end of round clears choice when the Risk is in the dueling line' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);
                $risk->Location = Game::LOCATION_DUELING_LINE;
                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01103,
                    'x',
                    1
                );

                $eor = new EventDuelEndOfRound();
                $eor->theah = $world->theah;
                $maneuver->handleEvent($eor);

                Assert::false($this->useParry($maneuver), 'cleared');
            },
        ];
    }
}
