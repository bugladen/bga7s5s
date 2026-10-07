<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01030_Test extends TestCase
{
    public function name(): string
    {
        return '_01030 Pull the Strand';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01030' => function () {
                $card = new _01030();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(1, $card->Riposte, 'Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::true($card->DashedThrust, 'DashedThrust');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::true($card->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($card->hasTrait('Sorte'), 'Sorte');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Action_01030::class, $card->getActions()[0], 'Action_01030');
            },
        ];
    }
}
