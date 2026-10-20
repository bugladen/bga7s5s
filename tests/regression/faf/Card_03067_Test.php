<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03067;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03067;

class Card_03067_Test extends TestCase
{
    public function name(): string
    {
        return '_03067 Ambitious';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral hubris risk' => function () {
                $card = new _03067();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(1, $card->WealthCost, 'wealth');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(1, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Hubris'), 'Hubris');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Action_03067::class, $card->getActions()[0], 'action');
            },
        ];
    }
}
