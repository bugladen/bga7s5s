<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01109;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01109;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01109_Test extends TestCase
{
    public function name(): string
    {
        return '_01109 Night of Drinking';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Risk with Reaction_01109 only' => function () {
                $card = new _01109();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::true($card->hasFaction('Castille'), 'Castille');
                Assert::same(1, $card->WealthCost, 'WealthCost');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Revelry'), 'Revelry');
                Assert::true($card->hasTrait('Torpor'), 'Torpor');
                Assert::count(1, $card->getReactions(), 'one reaction');
                Assert::instanceOf(Reaction_01109::class, $card->getReactions()[0], 'Reaction_01109');
            },
        ];
    }
}
