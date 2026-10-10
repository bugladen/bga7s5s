<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01157;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01157;

class Card_01157_Test extends TestCase
{
    public function name(): string
    {
        return '_01157 Throwing Knife';
    }

    public function tests(): array
    {
        return [
            // WHY: Multi-faction starter inclusion (Castille/Eisen) — Neutral is intentional.
            'constructs Neutral Weapon Ranged Knife FactionAttachment' => function () {
                $knife = new _01157();
                Assert::instanceOf(FactionAttachment::class, $knife, 'FactionAttachment');
                Assert::instanceOf(IHasTechniques::class, $knife, 'techniques');
                Assert::true($knife->hasFaction('Neutral'), 'Neutral');
                Assert::true($knife->hasTrait('Weapon'), 'Weapon');
                Assert::true($knife->hasTrait('Ranged'), 'Ranged');
                Assert::true($knife->hasTrait('Knife'), 'Knife');
            },

            'costs 0 Wealth with dashed Riposte, Parry 2, Thrust 3' => function () {
                $knife = new _01157();
                Assert::same(0, $knife->WealthCost, 'wealth');
                Assert::same(0, $knife->Riposte, 'Riposte');
                Assert::true($knife->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $knife->Parry, 'Parry');
                Assert::same(3, $knife->Thrust, 'Thrust');
            },

            'has exactly one Technique_01157 owned by the knife' => function () {
                $world = new TestWorld();
                $knife = $world->placeCard(new _01157(), Game::LOCATION_HAND, 1);
                Assert::count(1, $knife->getTechniques(), 'one');
                Assert::instanceOf(Technique_01157::class, $knife->getTechniques()[0], 'Technique_01157');
                Assert::same($knife->Id, $knife->getTechniques()[0]->OwnerId, 'owner');
            },

            'starts unattached' => function () {
                Assert::false((new _01157())->isAttached(), 'not attached');
            },
        ];
    }
}
