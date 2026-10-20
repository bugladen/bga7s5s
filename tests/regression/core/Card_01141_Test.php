<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01141;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01141;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01141_Test extends TestCase
{
    public function name(): string
    {
        return '_01141 Strong Hands';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Brawl Kulachniy Boi Risk with Action_01141' => function () {
                $risk = new _01141();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(1, $risk->WealthCost, 'cost');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(4, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Ussura'), 'Ussura');
                Assert::true($risk->hasTrait('Brawl'), 'Brawl');
                Assert::true($risk->hasTrait('Kulachniy Boi'), 'Kulachniy Boi');
                Assert::instanceOf(Action_01141::class, $risk->getActions()[0], 'Action_01141');
            },

            'Action OwnerId is wired to the Risk after construction' => function () {
                $risk = new _01141();
                $risk->setId(1141);
                Assert::same(1141, $risk->getActions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
