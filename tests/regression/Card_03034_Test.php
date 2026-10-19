<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03034;

class Card_03034_Test extends TestCase
{
    public function name(): string
    {
        return '_03034 La Voix des Sans Voix';
    }

    public function tests(): array
    {
        return [
            'constructs a unique camaraderie risk' => function () {
                $risk = new _03034();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets characters');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::same(1, $risk->Riposte, 'riposte');
                Assert::same(1, $risk->Parry, 'parry');
                Assert::same(1, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($risk->hasTrait('Inspiring'), 'Inspiring');
                Assert::true($risk->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::true($risk->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Action_03034::class, $risk->getActions()[0], 'action');
            },
        ];
    }
}
