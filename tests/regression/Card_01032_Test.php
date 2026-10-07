<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01032_Test extends TestCase
{
    public function name(): string
    {
        return '_01032 Unyielding Loyalty';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Reaction_01032' => function () {
                $card = new _01032();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::true($card->DashedParry, 'DashedParry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::true($card->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::true($card->hasTrait('Zeal'), 'Zeal');
                Assert::instanceOf(Reaction_01032::class, $card->getReactions()[0], 'Reaction_01032');
            },
        ];
    }
}
