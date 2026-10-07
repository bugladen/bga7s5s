<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;

class Card_01048_Test extends TestCase
{
    public function name(): string
    {
        return '_01048 Langschwert';
    }

    public function tests(): array
    {
        return [
            'constructs FactionAttachment with two PlusOneThrust techniques' => function () {
                $sword = new _01048();
                Assert::instanceOf(FactionAttachment::class, $sword, 'FactionAttachment');
                Assert::instanceOf(IHasTechniques::class, $sword, 'techniques');
                Assert::same(0, $sword->WealthCost, 'WealthCost');
                Assert::true($sword->DashedRiposte, 'DashedRiposte');
                Assert::same(2, $sword->Parry, 'Parry');
                Assert::same(3, $sword->Thrust, 'Thrust');
                Assert::true($sword->hasTrait('Weapon'), 'Weapon');
                Assert::true($sword->hasTrait('Melee'), 'Melee');
                Assert::true($sword->hasTrait('Sword'), 'Sword');
                Assert::true($sword->hasFaction('Eisen'), 'Eisen');
                $techniques = $sword->getTechniques();
                Assert::count(2, $techniques, 'two techniques');
                Assert::instanceOf(Technique_PlusOneThrust::class, $techniques[0], 'tech1');
                Assert::instanceOf(Technique_PlusOneThrust::class, $techniques[1], 'tech2');
                Assert::same('Technique_01048_1', $techniques[0]->Id, 'id1');
                Assert::same('Technique_01048_2', $techniques[1]->Id, 'id2');
            },
        ];
    }
}
