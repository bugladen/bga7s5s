<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;

class Card_01022_Test extends TestCase
{
    public function name(): string
    {
        return '_01022 Stiletto';
    }

    public function tests(): array
    {
        return [
            'constructs FactionAttachment with Reaction_01022' => function () {
                $stiletto = new _01022();
                Assert::instanceOf(FactionAttachment::class, $stiletto, 'FactionAttachment');
                Assert::instanceOf(IHasReactions::class, $stiletto, 'reactions');
                Assert::same(1, $stiletto->FinesseModifier, 'FinesseModifier');
                Assert::same(2, $stiletto->Riposte, 'Riposte');
                Assert::true($stiletto->DashedParry, 'DashedParry');
                Assert::same(2, $stiletto->Thrust, 'Thrust');
                Assert::same(1, $stiletto->WealthCost, 'WealthCost');
                Assert::true($stiletto->hasTrait('Weapon'), 'Weapon');
                Assert::true($stiletto->hasTrait('Melee'), 'Melee');
                Assert::true($stiletto->hasTrait('Knife'), 'Knife');
                Assert::true($stiletto->hasTrait('Ambrogia'), 'Ambrogia');
                Assert::instanceOf(Reaction_01022::class, $stiletto->getReactions()[0], 'Reaction_01022');
            },
        ];
    }
}
