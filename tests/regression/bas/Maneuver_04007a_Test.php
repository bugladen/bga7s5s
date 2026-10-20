<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04007a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04007b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_04007a_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04007a';
    }

    public function tests(): array
    {
        return [
            // WHY: The wound discount lives only on maneuver a. Card::getManeuverFromCombatCardDiscount
            // sums every maneuver, so b must stay at 0 or the discount doubles.
            'duelist only, shifts thrust to riposte, and discounts once' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04007(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                /** @var Maneuver_04007a $a */
                $a = $risk->getManeuvers()[0];
                /** @var Maneuver_04007b $b */
                $b = $risk->getManeuvers()[1];
                Assert::true($a->isAvailableToPlayer(1, $world->theah), 'duelist');
                $actor->ModifiedTraits = ['Hero'];
                Assert::false($a->isAvailableToPlayer(1, $world->theah), 'not a duelist');
                $actor->ModifiedTraits = ['Duelist'];

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $a->Id;
                $calc->thrust = 3;
                $calc->riposte = 0;
                $calc->theah = $world->theah;
                $a->handleEvent($calc);
                Assert::same(0, $calc->thrust, 'minus 3');
                Assert::same(2, $calc->riposte, 'plus 2');

                $foe->Wounds = 2;
                $explanations = [];
                Assert::same(1, $a->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'a discounts');
                Assert::same(0, $b->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'b does not');
                $actor->Wounds = 2;
                Assert::same(0, $a->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'not more wounds');
            },
        ];
    }
}
