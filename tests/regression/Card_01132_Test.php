<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01132;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01132;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01132_Test extends TestCase
{
    public function name(): string
    {
        return '_01132 Matushka\'s Command';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Sorcery Dar Matushki Risk with Action' => function () {
                $risk = new _01132();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(1, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(0, $risk->Thrust, 'Thrust');
                Assert::true($risk->DashedThrust, 'dashed Thrust');
                Assert::true($risk->hasFaction('Ussura'), 'Ussura');
                Assert::true($risk->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($risk->hasTrait('Dar Matushki'), 'Dar Matushki');
                Assert::instanceOf(Action_01132::class, $risk->getActions()[0], 'Action_01132');
            },
        ];
    }
}
