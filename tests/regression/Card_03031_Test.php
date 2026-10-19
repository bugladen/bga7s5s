<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03031;

class Card_03031_Test extends TestCase
{
    public function name(): string
    {
        return '_03031 Altruistic';
    }

    public function tests(): array
    {
        return [
            'constructs a Montaigne virtue risk' => function () {
                $risk = new _03031();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::true($risk->DashedRiposte, 'dashed riposte');
                Assert::same(2, $risk->Parry, 'parry');
                Assert::same(2, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($risk->hasTrait('Virtue'), 'Virtue');
                Assert::instanceOf(Reaction_03031::class, $risk->getReactions()[0], 'reaction');
            },
        ];
    }
}
