<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_03008_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03008';
    }

    /**
     * @return array{0:_03008,1:Maneuver_03008,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03008(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Proud'), Game::LOCATION_CITY_DOCKS, 1);
        $actor->ModifiedInfluence = 3;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->ModifiedInfluence = 1;
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::DUEL_GAMBLED, true);
        /** @var Maneuver_03008 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    public function tests(): array
    {
        return [
            'is a maneuver' => function () {
                Assert::instanceOf(Maneuver::class, new Maneuver_03008(), 'maneuver');
            },

            'available only after a gamble when the actor has strictly more Influence' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');

                $foe->ModifiedInfluence = 3;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'equal');
                $foe->ModifiedInfluence = 1;

                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not gambled');

                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                $world->theah->duelOpponent = null;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'no adversary');
            },

            // WHY: The Influence comparison is availability only. Calculate still adds Riposte
            // when this maneuver id matches.
            'calculate adds 1 Riposte and resolve draws for the event player' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->scene($world);
                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->riposte = 2;
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);
                Assert::same(3, $calc->riposte, 'riposte');
                Assert::contains($risk->getInjectCode(), $calc->explanations[0], 'explanation');

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $resolve->playerId = 1;
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);
                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::same(1, $draws[0]->playerId, 'drawer');
            },

            'a different maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);
                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = 'other';
                $calc->riposte = 2;
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->riposte, 'unchanged');
            },
        ];
    }
}
