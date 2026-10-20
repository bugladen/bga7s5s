<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01028_Test extends TestCase
{
    public function name(): string
    {
        return '_01028 Pack Tactics';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Action_01028' => function () {
                $card = new _01028();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::true($card->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::true($card->hasTrait('Gang'), 'Gang');
                Assert::instanceOf(Action_01028::class, $card->getActions()[0], 'Action_01028');
            },
        ];
    }
}
