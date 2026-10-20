<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03070;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class Maneuver_03070_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03070';
    }

    public function tests(): array
    {
        return [
            // WHY: Excess is starting threat minus the adversary's CHALLENGE_STAT value.
            // Combat is the default. The actor's seat gets the negative delta. No excess
            // queues nothing. A new round forgets the discarded amount.
            'discards starting threat in excess of the adversary duel stat' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03070(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $maneuver = $risk->getManeuvers()[0];
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $world->theah->startingDuelThreats[$actor->Id] = 1;
                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventThreatModified::class), 'no excess');
                Assert::same(0, $maneuver->ExcessDiscarded, 'none');

                $world->theah->startingDuelThreats[$actor->Id] = 5;
                $maneuver->handleEvent($resolve);
                $threat = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::same(-4, $threat->challengerThreat, 'challenger seat');
                Assert::same(0, $threat->defenderThreat, 'not defender');
                Assert::same(4, $maneuver->ExcessDiscarded, 'remembered');

                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);
                $foe->ModifiedFinesse = 3;
                $world->theah->queuedEvents = [];
                $maneuver->handleEvent($resolve);
                Assert::same(-2, $world->theah->queuedOfType(EventThreatModified::class)[0]->challengerThreat, 'finesse');

                $world->theah->duelChallengerIdOverride = $foe->Id;
                $world->game->globals->delete(Game::CHALLENGE_STAT);
                $world->theah->queuedEvents = [];
                $maneuver->handleEvent($resolve);
                $threat = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::same(0, $threat->challengerThreat, 'not challenger');
                Assert::same(-4, $threat->defenderThreat, 'defender seat');

                $round = new EventDuelNewRound();
                $round->theah = $world->theah;
                $maneuver->handleEvent($round);
                Assert::same(0, $maneuver->ExcessDiscarded, 'new round');
            },
        ];
    }
}
