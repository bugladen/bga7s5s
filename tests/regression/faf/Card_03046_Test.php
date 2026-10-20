<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03046;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03046a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03046b;

class Card_03046_Test extends TestCase
{
    public function name(): string
    {
        return '_03046 Passionate';
    }

    public function tests(): array
    {
        return [
            'constructs a Castille virtue risk with two reactions' => function () {
                $risk = new _03046();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::same(1, $risk->WealthCost, 'wealth');
                Assert::true($risk->DashedRiposte, 'dashed riposte');
                Assert::same(2, $risk->Parry, 'parry');
                Assert::same(2, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Virtue'), 'Virtue');
                Assert::instanceOf(Reaction_03046a::class, $risk->getReactions()[0], 'duelist');
                Assert::instanceOf(Reaction_03046b::class, $risk->getReactions()[1], 'pirate');
            },
        ];
    }
}
