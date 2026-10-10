<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01128;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01128;

class Card_01128_Test extends TestCase
{
    public function name(): string
    {
        return '_01128 Mireli Sabre';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Mireli Sword FactionAttachment' => function () {
                $sabre = new _01128();
                Assert::instanceOf(FactionAttachment::class, $sabre, 'FactionAttachment');
                Assert::instanceOf(IHasTechniques::class, $sabre, 'techniques');
                Assert::true($sabre->hasFaction('Ussura'), 'Ussura');
                Assert::true($sabre->hasTrait('Weapon'), 'Weapon');
                Assert::true($sabre->hasTrait('Melee'), 'Melee');
                Assert::true($sabre->hasTrait('Sword'), 'Sword');
                Assert::true($sabre->hasTrait('Mireli'), 'Mireli');
            },

            // WHY: FactionAttachment pre-commit requires Riposte set; pin dashed Parry + duel line.
            'costs 0 Wealth with Riposte 2, dashed Parry 0, Thrust 1' => function () {
                $sabre = new _01128();
                Assert::same(0, $sabre->WealthCost, 'wealth');
                Assert::same(2, $sabre->Riposte, 'Riposte');
                Assert::same(0, $sabre->Parry, 'Parry');
                Assert::true($sabre->DashedParry, 'dashed Parry');
                Assert::same(1, $sabre->Thrust, 'Thrust');
                Assert::same(0, $sabre->ResolveModifier, 'Resolve');
                Assert::same(0, $sabre->CombatModifier, 'Combat');
                Assert::same(0, $sabre->FinesseModifier, 'Finesse');
                Assert::same(0, $sabre->InfluenceModifier, 'Influence');
            },

            'has exactly one Technique_01128 owned by the sabre' => function () {
                $world = new TestWorld();
                $sabre = $world->placeCard(new _01128(), Game::LOCATION_HAND, 1);
                Assert::count(1, $sabre->getTechniques(), 'one');
                Assert::instanceOf(Technique_01128::class, $sabre->getTechniques()[0], 'Technique_01128');
                Assert::same($sabre->Id, $sabre->getTechniques()[0]->OwnerId, 'owner');
            },

            'starts unattached' => function () {
                Assert::false((new _01128())->isAttached(), 'not attached');
            },
        ];
    }
}
