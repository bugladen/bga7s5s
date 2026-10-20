<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04030;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_04030_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04030';
    }

    public function tests(): array
    {
        return [
            // WHY: The combat-card discount is this maneuver's own channel, and only when
            // the actor is a Merchant or Scoundrel. Choice 1 is Parry; anything else is
            // Thrust. Cancel clears the Parry choice back to Thrust. Activation is stacked.
            'chooses parry or thrust, and discounts a scoundrel actor' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04030(), Game::LOCATION_DUELING_LINE, 1);
                $other = $world->placeCard(new _04027(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new _04022(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $actor;
                /** @var Maneuver_04030 $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                $explanations = [];
                Assert::same(1, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'this card, scoundrel');
                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $other, $explanations), 'other card');
                $world->theah->duelActor = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'hero actor');

                $activated = new EventManeuverActivated();
                $activated->maneuverId = $maneuver->Id;
                $activated->theah = $world->theah;
                $maneuver->handleEvent($activated);
                Assert::same('04030', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'choice');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_04030, '', 1);
                $parry = new EventDuelCalculateManeuverValues();
                $parry->maneuverId = $maneuver->Id;
                $parry->theah = $world->theah;
                $maneuver->handleEvent($parry);
                Assert::same(1, $parry->parry, 'parry');
                Assert::same(0, $parry->thrust, 'not thrust');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_04030, '', 2);
                $thrust = new EventDuelCalculateManeuverValues();
                $thrust->maneuverId = $maneuver->Id;
                $thrust->theah = $world->theah;
                $maneuver->handleEvent($thrust);
                Assert::same(1, $thrust->thrust, 'thrust');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_04030, '', 1);
                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);
                $after = new EventDuelCalculateManeuverValues();
                $after->maneuverId = $maneuver->Id;
                $after->theah = $world->theah;
                $maneuver->handleEvent($after);
                Assert::same(1, $after->thrust, 'cancel returns to thrust');
                Assert::same(0, $after->parry, 'parry cleared');
            },
        ];
    }
}
