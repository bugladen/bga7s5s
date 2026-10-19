<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03069;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_03069b_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03069b';
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling still grants +1 Riposte when Harpooned or alone. Those cases skip
            // the chooser. Activate does not throw. A legal swap still opens the shared picker.
            'adds one riposte and swaps only when it can' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03069(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $maneuver = $risk->getManeuvers()[1];
                $world->theah->duelActor = $actor;
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'alone is fine');

                $actor->addCondition(Game::HARPOON_CONDITION);
                $activated = new EventManeuverActivated();
                $activated->theah = $world->theah;
                $activated->maneuverId = $maneuver->Id;
                $maneuver->eventCheck($activated);

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'harpoon skips chooser');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(1, $calc->riposte, 'riposte');

                $actor->removeCondition(Game::HARPOON_CONDITION);
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                $maneuver->handleEvent($resolve);
                Assert::same('03069', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'chooser');
            },
        ];
    }
}
