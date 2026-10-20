<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03011;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_03011_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03011';
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling Maneuver. The Thug or Bodyguard must be yours and at the
            // actor's location. The actor themselves counts. Calculate does not recheck.
            'gambled, with your Thug at the duel, adds 1 Riposte' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03011(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $actor;
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                /** @var Maneuver_03011 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'no thug');

                $actor->addTrait($world->game, 'Thug', true);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'actor is the thug');

                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);

                $foeThug = $world->placeCharacter(new GenericCharacter('Foe', ['Bodyguard']), Game::LOCATION_CITY_DOCKS, 2);
                $actor->removeTrait($world->game, 'Thug');
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'opponent bodyguard does not count');
                unset($foeThug);

                $world->placeCharacter(new GenericCharacter('Ally', ['Bodyguard']), Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'ally bodyguard');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(1, $calc->riposte, 'plus one');
                Assert::same(0, $calc->thrust, 'thrust untouched');
            },
        ];
    }
}
