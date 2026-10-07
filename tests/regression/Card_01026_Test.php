<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01026_Test extends TestCase
{
    public function name(): string
    {
        return '_01026 For the Family';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01026 and combat values' => function () {
                $card = new _01026();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::same(3, $card->Parry, 'Parry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::true($card->hasTrait('Glory'), 'Glory');
                Assert::true($card->hasTrait('Zeal'), 'Zeal');
                Assert::true($card->hasFaction('Vodacce'), 'Faction');
                Assert::instanceOf(Action_01026::class, $card->getActions()[0], 'Action_01026');
            },
        ];
    }
}
