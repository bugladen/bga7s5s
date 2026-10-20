<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01034_Test extends TestCase
{
    public function name(): string
    {
        return '_01034 Wrath of the Don';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01034' => function () {
                $card = new _01034();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::true($card->DashedRiposte, 'DashedRiposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(3, $card->Thrust, 'Thrust');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::true($card->hasTrait('Demoralize'), 'Demoralize');
                Assert::true($card->hasTrait('Duress'), 'Duress');
                Assert::true($card->hasTrait('Zeal'), 'Zeal');
                Assert::instanceOf(Action_01034::class, $card->getActions()[0], 'Action_01034');
            },
        ];
    }
}
