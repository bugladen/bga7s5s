<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_03009_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03009';
    }

    /**
     * @return array{0:_03009,1:Maneuver_03009,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03009(), \Bga\Games\SeventhSeaCityOfFiveSails\Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Strand', ['Strega', 'Duelist']), \Bga\Games\SeventhSeaCityOfFiveSails\Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), \Bga\Games\SeventhSeaCityOfFiveSails\Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_03009 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    public function tests(): array
    {
        return [
            'is a maneuver' => function () {
                Assert::instanceOf(Maneuver::class, new Maneuver_03009(), 'maneuver');
            },

            'available when the duel actor is a Strega, with no gamble requirement' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'strega actor');

                // WHY: hasTrait treats an empty ModifiedTraits as uninitialized and copies Traits
                // back. Removing the last trait would not stick, so a second trait stays behind.
                $actor->removeTrait($world->game, 'Strega');
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a strega');

                $world->theah->duelActor = null;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'no actor');
            },

            'calculate subtracts 1 Thrust and resolve wounds the adversary' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->scene($world);
                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->thrust = 2;
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);
                Assert::same(1, $calc->thrust, 'thrust');
                Assert::contains($risk->getInjectCode(), $calc->explanations[0], 'explanation');

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::same($foe->Id, $wound[0]->characterId, 'adversary');
                Assert::same($risk->Id, $wound[0]->sourceId, 'risk');
                Assert::same($maneuver->Id, $wound[0]->abilityId, 'ability');
                Assert::same(1, $wound[0]->wounds, 'one');
            },

            'a different maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);
                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = 'other';
                $calc->thrust = 2;
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->thrust, 'unchanged');
                Assert::count(0, $world->theah->queuedEvents, 'no wound');
            },
        ];
    }
}
