<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03045;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_03045_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03045';
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling is the only gate. Resolve wounds the participant, not the
            // adversary. Calculate is +2 Riposte and does not recheck the gamble.
            'wounds your participant and adds 2 Riposte' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03045(), Game::LOCATION_DUELING_LINE, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $actor;
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                /** @var Maneuver_03045 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not gambled');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->riposte, 'two riposte');

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::same($actor->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'participant');
            },
        ];
    }
}
