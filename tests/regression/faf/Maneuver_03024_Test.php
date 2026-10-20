<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03024;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03024;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_03024_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03024';
    }

    /**
     * @return array{0:_03024,1:Maneuver_03024,2:GenericCharacter}
     */
    private function scene(TestWorld $world, array $traits = ['Sorcerer']): array
    {
        $risk = $world->placeCard(new _03024(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Adversary', $traits), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        /** @var Maneuver_03024 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $adversary];
    }

    public function tests(): array
    {
        return [
            'is on a wealth-1 Eisen risk' => function () {
                $risk = new _03024();
                Assert::same(1, $risk->WealthCost, 'wealth');
                Assert::true($risk->hasFaction('Eisen'), 'Eisen');
                Assert::true($risk->hasTrait('Faith'), 'Faith');
                Assert::instanceOf(Maneuver_03024::class, $risk->getManeuvers()[0], 'maneuver');
            },

            // WHY: Sorcerer or Monster. There is no gamble gate. Id 1 is Parry and id 2
            // is Thrust. Until a choice, calculate treats the flag as Thrust. Cancel
            // forgets a Parry choice. Activate stacks the picker ahead of the resolve.
            'chooses +2 Parry or +2 Thrust against a Sorcerer or Monster' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $adversary] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'sorcerer');
                $adversary->addTrait($world->game, 'Hero', true);
                $adversary->removeTrait($world->game, 'Sorcerer');
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'neither');
                $adversary->addTrait($world->game, 'Monster', true);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'monster');

                $activated = new EventManeuverActivated();
                $activated->theah = $world->theah;
                $activated->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($activated);
                Assert::same('03024', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'risk');
                Assert::true($maneuver->Used, 'parent marks it used on activate');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03024, 'x', 1);
                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->parry, 'parry');
                Assert::same(0, $calc->thrust, 'not thrust');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03024, 'x', 2);
                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->thrust, 'thrust');
                Assert::same(0, $calc->parry, 'not parry');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03024, 'x', 1);
                $canceled = new EventManeuverCanceled();
                $canceled->theah = $world->theah;
                $canceled->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($canceled);
                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->thrust, 'cancel returns to thrust');
            },
        ];
    }
}
