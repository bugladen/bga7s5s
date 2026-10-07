<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01029_Test extends TestCase
{
    public function name(): string
    {
        return '_01029 The Pressure is On';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01029 and dashed combat values' => function () {
                $card = new _01029();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::true($card->DashedRiposte, 'DashedRiposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::true($card->DashedThrust, 'DashedThrust');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::true($card->hasTrait('Demoralize'), 'Demoralize');
                Assert::true($card->hasTrait('Duress'), 'Duress');
                Assert::instanceOf(Action_01029::class, $card->getActions()[0], 'Action_01029');
            },
        ];
    }
}
