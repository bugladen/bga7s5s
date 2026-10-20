<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04049;

class Card_04049_Test extends TestCase
{
    public function name(): string
    {
        return '_04049 Point of Order';
    }

    public function tests(): array
    {
        return [
            'is a bureaucracy authority risk' => function () {
                $card = new _04049();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(2, $card->Parry, 'parry');
                Assert::true($card->DashedThrust, 'dashed thrust');
                Assert::true($card->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($card->hasTrait('Authority'), 'Authority');
                Assert::true($card->hasTrait('Demoralize'), 'Demoralize');
                Assert::count(1, $card->getActions(), 'action');
            },
        ];
    }
}
