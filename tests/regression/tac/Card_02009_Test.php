<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02009;

class Card_02009_Test extends TestCase
{
    public function name(): string
    {
        return '_02009 Murder';
    }

    public function tests(): array
    {
        return [
            'constructs Vodacce Crime Risk with three Maneuver_02009 instances' => function () {
                $risk = new _02009();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::true($risk->hasTrait('Villainous'), 'Villainous');
                Assert::true($risk->hasTrait('Crime'), 'Crime');
                Assert::count(3, $risk->getManeuvers(), 'three');
                foreach ($risk->getManeuvers() as $maneuver) {
                    Assert::instanceOf(Maneuver_02009::class, $maneuver, 'Maneuver_02009');
                }
            },

            // WHY: three trait-gated maneuvers share Maneuver_02009 but need distinct construct ids
            // (ClassId). After placeCard, setOwnerId prefixes Id as "{ownerId}_{ClassId}".
            'construct pins Thug / Duelist / Spy ClassIds' => function () {
                $risk = new _02009();
                $classIds = array_map(fn($m) => $m->ClassId, $risk->getManeuvers());
                Assert::same(
                    ['Maneuver_02009_Thug', 'Maneuver_02009_Duelist', 'Maneuver_02009_Spy'],
                    $classIds,
                    'ClassIds'
                );
                Assert::same($classIds, array_map(fn($m) => $m->Id, $risk->getManeuvers()), 'Id before owner');
            },

            'owns all three maneuvers when placed with owner-prefixed Ids' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02009(), Game::LOCATION_HAND, 1);
                Assert::count(3, $risk->getManeuvers(), 'three');
                foreach ($risk->getManeuvers() as $maneuver) {
                    Assert::same($risk->Id, $maneuver->OwnerId, 'owner');
                    Assert::same("{$risk->Id}_{$maneuver->ClassId}", $maneuver->Id, 'prefixed id');
                }
            },
        ];
    }
}
