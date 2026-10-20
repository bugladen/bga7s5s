<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03032;

class Card_03032_Test extends TestCase
{
    public function name(): string
    {
        return '_03032 Bloody Entrance';
    }

    public function tests(): array
    {
        return [
            'constructs a Montaigne Porté risk' => function () {
                $risk = new _03032();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(1, $risk->WealthCost, 'wealth');
                Assert::same(2, $risk->Riposte, 'riposte');
                Assert::same(0, $risk->Parry, 'parry');
                Assert::same(1, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($risk->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($risk->hasTrait('Porté'), 'Porté');
                Assert::instanceOf(Action_03032::class, $risk->getActions()[0], 'action');
            },
        ];
    }
}
