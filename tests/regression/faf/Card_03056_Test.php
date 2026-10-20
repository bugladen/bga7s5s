<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03056;

class Card_03056_Test extends TestCase
{
    public function name(): string
    {
        return '_03056 Astute';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran cunning risk' => function () {
                $card = new _03056();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $card, 'targets');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(3, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Virtue'), 'Virtue');
                Assert::true($card->hasTrait('Cunning'), 'Cunning');
                Assert::instanceOf(Action_03056::class, $card->getActions()[0], 'action');
            },
        ];
    }
}
