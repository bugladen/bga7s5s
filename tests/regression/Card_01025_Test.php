<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01025_Test extends TestCase
{
    public function name(): string
    {
        return "_01025 Fate's Burden";
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01025' => function () {
                $burden = new _01025();
                Assert::instanceOf(Risk::class, $burden, 'Risk');
                Assert::instanceOf(IHasActions::class, $burden, 'actions');
                Assert::true($burden->DashedRiposte, 'DashedRiposte');
                Assert::same(2, $burden->Parry, 'Parry');
                Assert::same(2, $burden->Thrust, 'Thrust');
                Assert::same(0, $burden->WealthCost, 'WealthCost');
                Assert::true($burden->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($burden->hasTrait('Sorte'), 'Sorte');
                Assert::true($burden->hasFaction('Vodacce'), 'Faction');
                Assert::instanceOf(Action_01025::class, $burden->getActions()[0], 'Action_01025');
            },
        ];
    }
}
