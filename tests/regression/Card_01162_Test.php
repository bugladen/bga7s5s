<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01162;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01162;

class Card_01162_Test extends TestCase
{
    public function name(): string
    {
        return '_01162 Come Hither';
    }

    public function tests(): array
    {
        return [
            'constructs Romance Temptation Risk that targets characters' => function () {
                $risk = new _01162();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(2, $risk->WealthCost, 'cost');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Romance'), 'Romance');
                Assert::true($risk->hasTrait('Temptation'), 'Temptation');
                Assert::instanceOf(Action_01162::class, $risk->getActions()[0], 'Action_01162');
            },
        ];
    }
}
