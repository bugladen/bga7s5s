<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01158;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01158;

class Card_01158_Test extends TestCase
{
    public function name(): string
    {
        return '_01158 Uppman\'s Jacket';
    }

    public function tests(): array
    {
        return [
            // WHY: Appears in Castille/Montaigne/Eisen TaC starters — Neutral multi-faction attire.
            'constructs Neutral Attire Coat Rilasciare FactionAttachment' => function () {
                $jacket = new _01158();
                Assert::instanceOf(FactionAttachment::class, $jacket, 'FactionAttachment');
                Assert::instanceOf(IHasActions::class, $jacket, 'actions');
                Assert::true($jacket->hasFaction('Neutral'), 'Neutral');
                Assert::true($jacket->hasTrait('Attire'), 'Attire');
                Assert::true($jacket->hasTrait('Coat'), 'Coat');
                Assert::true($jacket->hasTrait('Rilasciare'), 'Rilasciare');
            },

            'costs 1 Wealth with +1 Influence, dashed Riposte, Parry/Thrust 2' => function () {
                $jacket = new _01158();
                Assert::same(1, $jacket->WealthCost, 'wealth');
                Assert::same(1, $jacket->InfluenceModifier, 'Influence');
                Assert::same(0, $jacket->Riposte, 'Riposte');
                Assert::true($jacket->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $jacket->Parry, 'Parry');
                Assert::same(2, $jacket->Thrust, 'Thrust');
            },

            'has exactly one Action_01158 owned by the jacket' => function () {
                $world = new TestWorld();
                $jacket = $world->placeCard(new _01158(), Game::LOCATION_HAND, 1);
                Assert::count(1, $jacket->getActions(), 'one');
                Assert::instanceOf(Action_01158::class, $jacket->getActions()[0], 'Action_01158');
                Assert::same($jacket->Id, $jacket->getActions()[0]->OwnerId, 'owner');
            },

            'starts unattached' => function () {
                Assert::false((new _01158())->isAttached(), 'not attached');
            },
        ];
    }
}
