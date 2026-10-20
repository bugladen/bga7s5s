<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04058;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;

class Maneuver_04058_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04058';
    }

    public function tests(): array
    {
        return [
            // WHY: The adversary must be engaged or the maneuver is hidden. +1 Riposte
            // is separate from the draw. Draw only while your participant is en garde.
            // Both sorcery events still queue when there is no draw.
            'draws only while your sorcerer is en garde' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04058(), Game::LOCATION_DUELING_LINE, 1);
                $sorcerer = $world->placeCharacter(new _04041(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $sorcerer;
                $world->theah->duelOpponent = $foe;
                /** @var Maneuver_04058 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'adversary is en garde');

                $foe->Engaged = true;
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'adversary engaged');

                $hero = $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $hero;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a sorcerer');
                $world->theah->duelActor = $sorcerer;

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->riposte = 0;
                $world->fireOn($risk, $calc);
                Assert::same(1, $calc->riposte, '+1 riposte');

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $resolve->playerId = 1;
                $world->fireOn($risk, $resolve);
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'en garde draws');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');

                $world->theah->takeQueuedEvents();
                $sorcerer->Engaged = true;
                $world->fireOn($risk, $resolve);
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'still starts');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'engaged does not draw');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'still played');
            },
        ];
    }
}
