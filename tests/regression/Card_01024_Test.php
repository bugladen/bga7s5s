<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01024;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01024;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01024_Test extends TestCase
{
    public function name(): string
    {
        return '_01024 Bravos';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01024' => function () {
                $bravos = new _01024();
                Assert::instanceOf(Risk::class, $bravos, 'Risk');
                Assert::instanceOf(IHasActions::class, $bravos, 'actions');
                Assert::same(1, $bravos->Riposte, 'Riposte');
                Assert::same(1, $bravos->Parry, 'Parry');
                Assert::same(3, $bravos->Thrust, 'Thrust');
                Assert::same(1, $bravos->WealthCost, 'WealthCost');
                Assert::true($bravos->hasTrait('Conscription'), 'Conscription');
                Assert::true($bravos->hasTrait('Gang'), 'Gang');
                Assert::true($bravos->hasFaction('Vodacce'), 'Faction');
                Assert::instanceOf(Action_01024::class, $bravos->getActions()[0], 'Action_01024');
            },
        ];
    }
}
