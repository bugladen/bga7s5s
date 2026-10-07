<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01046;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01046a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01046b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class Card_01046_Test extends TestCase
{
    public function name(): string
    {
        return '_01046 Dark Gift';
    }

    public function tests(): array
    {
        return [
            'constructs FactionAttachment with Action_01046a and Action_01046b' => function () {
                $gift = new _01046();
                Assert::instanceOf(FactionAttachment::class, $gift, 'FactionAttachment');
                Assert::instanceOf(IHasActions::class, $gift, 'actions');
                Assert::same(1, $gift->WealthCost, 'WealthCost');
                Assert::same(3, $gift->Riposte, 'Riposte');
                Assert::same(0, $gift->Parry, 'Parry');
                Assert::true($gift->DashedParry, 'DashedParry');
                Assert::same(0, $gift->Thrust, 'Thrust');
                Assert::true($gift->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($gift->hasTrait('Unique'), 'Unique');
                Assert::true($gift->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Action_01046a::class, $gift->getActions()[0], '01046a');
                Assert::instanceOf(Action_01046b::class, $gift->getActions()[1], '01046b');
            },
        ];
    }
}
