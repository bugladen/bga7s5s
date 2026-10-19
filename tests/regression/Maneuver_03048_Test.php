<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03048;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_03048_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03048';
    }

    public function tests(): array
    {
        return [
            // WHY: The discount is only while this card was gambled. Riposte equals the
            // participant's current threat, which is how threat moves to the adversary.
            // Zero threat adds nothing.
            'moves the participant\'s threat onto the adversary as Riposte' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03048(), Game::LOCATION_DUELING_LINE, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Scoundrel', ['Scoundrel']), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $actor;
                $world->theah->duelThreats[$actor->Id] = 4;
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                /** @var Maneuver_03048 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'scoundrel');
                $actor->addTrait($world->game, 'Hero', true);
                $actor->removeTrait($world->game, 'Scoundrel');
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a scoundrel');

                $explanations = [];
                Assert::same(1, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'gambled discount');
                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'not gambled');
                $other = $world->placeCard(new _03048(), Game::LOCATION_DUELING_LINE, 1);
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $other, $explanations), 'another card');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(4, $calc->riposte, 'all of the threat');

                $world->theah->duelThreats[$actor->Id] = 0;
                $empty = new EventDuelCalculateManeuverValues();
                $empty->theah = $world->theah;
                $empty->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($empty);
                Assert::same(0, $empty->riposte, 'no threat');
                Assert::count(0, $empty->explanations, 'no explanation');
            },
        ];
    }
}
