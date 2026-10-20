<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01134;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01134;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01134_Test extends TestCase
{
    public function name(): string
    {
        return '_01134 Matushka\'s Sight';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Sorcery Dar Matushki Risk with Action' => function () {
                $risk = new _01134();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(0, $risk->WealthCost, 'Wealth');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(1, $risk->Parry, 'Parry');
                Assert::same(4, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasFaction('Ussura'), 'Ussura');
                Assert::true($risk->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($risk->hasTrait('Dar Matushki'), 'Dar Matushki');
                Assert::instanceOf(Action_01134::class, $risk->getActions()[0], 'Action_01134');
            },
        ];
    }
}
