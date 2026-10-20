<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01049_Test extends TestCase
{
    public function name(): string
    {
        return '_01049 Polished Flintlock';
    }

    public function tests(): array
    {
        return [
            'constructs FactionAttachment with Action and Technique' => function () {
                $flint = new _01049();
                Assert::instanceOf(FactionAttachment::class, $flint, 'FactionAttachment');
                Assert::instanceOf(IHasActions::class, $flint, 'actions');
                Assert::instanceOf(IHasTechniques::class, $flint, 'techniques');
                Assert::same(2, $flint->WealthCost, 'WealthCost');
                Assert::same(1, $flint->FinesseModifier, 'FinesseModifier');
                Assert::same(1, $flint->Riposte, 'Riposte');
                Assert::same(2, $flint->Parry, 'Parry');
                Assert::true($flint->DashedThrust, 'DashedThrust');
                Assert::true($flint->hasTrait('Weapon'), 'Weapon');
                Assert::true($flint->hasTrait('Ranged'), 'Ranged');
                Assert::true($flint->hasTrait('Pistol'), 'Pistol');
                Assert::true($flint->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Technique_01049::class, $flint->getTechniques()[0], 'Technique_01049');
                Assert::instanceOf(Action_01049::class, $flint->getActions()[0], 'Action_01049');
            },
        ];
    }
}
