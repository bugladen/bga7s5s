<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01156;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01156;

class Card_01156_Test extends TestCase
{
    public function name(): string
    {
        return '_01156 Matchlock Musket';
    }

    public function tests(): array
    {
        return [
            // WHY: StarterDecks put Matchlock in Castille/Eisen/TaC lists — Neutral is intentional
            // (multi-faction weapon), not a missing initializeFaction.
            'constructs Neutral Weapon Ranged Rifle FactionAttachment' => function () {
                $musket = new _01156();
                Assert::instanceOf(FactionAttachment::class, $musket, 'FactionAttachment');
                Assert::instanceOf(IHasActions::class, $musket, 'actions');
                Assert::true($musket->hasFaction('Neutral'), 'Neutral');
                Assert::true($musket->hasTrait('Weapon'), 'Weapon');
                Assert::true($musket->hasTrait('Ranged'), 'Ranged');
                Assert::true($musket->hasTrait('Rifle'), 'Rifle');
            },

            // WHY: FactionAttachment pre-commit requires Riposte set; pin duel line + cost.
            'costs 1 Wealth with dashed Riposte/Parry and Thrust 5' => function () {
                $musket = new _01156();
                Assert::same(1, $musket->WealthCost, 'wealth');
                Assert::same(0, $musket->Riposte, 'Riposte');
                Assert::true($musket->DashedRiposte, 'dashed Riposte');
                Assert::same(0, $musket->Parry, 'Parry');
                Assert::true($musket->DashedParry, 'dashed Parry');
                Assert::same(5, $musket->Thrust, 'Thrust');
                Assert::same(0, $musket->InfluenceModifier, 'Influence');
            },

            'has exactly one Action_01156 owned by the musket' => function () {
                $world = new TestWorld();
                $musket = $world->placeCard(new _01156(), Game::LOCATION_HAND, 1);
                Assert::count(1, $musket->getActions(), 'one');
                Assert::instanceOf(Action_01156::class, $musket->getActions()[0], 'Action_01156');
                Assert::same($musket->Id, $musket->getActions()[0]->OwnerId, 'owner');
            },

            'starts unattached' => function () {
                Assert::false((new _01156())->isAttached(), 'not attached');
            },
        ];
    }
}
