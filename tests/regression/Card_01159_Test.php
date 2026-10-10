<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01159;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01159a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01159b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01159;

class Card_01159_Test extends TestCase
{
    public function name(): string
    {
        return '_01159 Appealing to the People';
    }

    public function tests(): array
    {
        return [
            // WHY: Printed typo "Beauracracy" is on the card class — pin as-is so a "fix" doesn't
            // silently change the trait string the rules text / UI already shipped with.
            'constructs Neutral Bureaucracy Heroic Risk with Action_01159' => function () {
                $risk = new _01159();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::true($risk->hasTrait('Beauracracy'), 'Beauracracy typo');
                Assert::true($risk->hasTrait('Heroic'), 'Heroic');
                Assert::instanceOf(Action_01159::class, $risk->getActions()[0], 'Action_01159');
            },

            'costs 2 Wealth with dashed Riposte, Parry 3, Thrust 1' => function () {
                $risk = new _01159();
                Assert::same(2, $risk->WealthCost, 'wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(3, $risk->Parry, 'Parry');
                Assert::same(1, $risk->Thrust, 'Thrust');
            },

            'Action OwnerId is wired after setId' => function () {
                $risk = new _01159();
                $risk->setId(1159);
                Assert::same(1159, $risk->getActions()[0]->OwnerId, 'owner');
            },

            // WHY: CORE vs PROMO art variants only override Image — shared identity/stats/action.
            '_01159a is a CORE art variant of _01159' => function () {
                $base = new _01159();
                $a = new _01159a();
                Assert::instanceOf(_01159::class, $a, 'extends');
                Assert::same('01159a.jpg', $a->Image, 'CORE art');
                Assert::same($base->CardNumber, $a->CardNumber, 'number');
                Assert::same($base->WealthCost, $a->WealthCost, 'cost');
                Assert::instanceOf(Action_01159::class, $a->getActions()[0], 'same Action');
            },

            '_01159b is a PROMO art variant of _01159' => function () {
                $base = new _01159();
                $b = new _01159b();
                Assert::instanceOf(_01159::class, $b, 'extends');
                Assert::same('01159b.jpg', $b->Image, 'PROMO art');
                Assert::same($base->CardNumber, $b->CardNumber, 'number');
                Assert::same($base->WealthCost, $b->WealthCost, 'cost');
                Assert::instanceOf(Action_01159::class, $b->getActions()[0], 'same Action');
            },

            'variants wire Action OwnerId like the base card' => function () {
                $world = new TestWorld();
                $a = $world->placeCard(new _01159a(), Game::LOCATION_HAND, 1);
                $b = $world->placeCard(new _01159b(), Game::LOCATION_HAND, 1);
                Assert::same($a->Id, $a->getActions()[0]->OwnerId, 'a owner');
                Assert::same($b->Id, $b->getActions()[0]->OwnerId, 'b owner');
            },
        ];
    }
}
