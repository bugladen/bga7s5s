<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01023_Test extends TestCase
{
    public function name(): string
    {
        return '_01023 Ambush';
    }

    public function tests(): array
    {
        return [
            'constructs Risk with Reaction_01023' => function () {
                $ambush = new _01023();
                Assert::instanceOf(Risk::class, $ambush, 'Risk');
                Assert::instanceOf(IHasReactions::class, $ambush, 'reactions');
                Assert::true($ambush->DashedRiposte, 'DashedRiposte');
                Assert::same(2, $ambush->Parry, 'Parry');
                Assert::same(3, $ambush->Thrust, 'Thrust');
                Assert::same(1, $ambush->WealthCost, 'WealthCost');
                Assert::true($ambush->hasTrait('Brawl'), 'Brawl');
                Assert::true($ambush->hasTrait('Gang'), 'Gang');
                Assert::true($ambush->hasFaction('Vodacce'), 'Faction');
                Assert::instanceOf(Reaction_01023::class, $ambush->getReactions()[0], 'Reaction_01023');
            },
        ];
    }
}
