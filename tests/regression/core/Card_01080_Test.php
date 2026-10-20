<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01080;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01080;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01080_Test extends TestCase
{
    public function name(): string
    {
        return '_01080 Friends at Court';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk with Reaction_01080 only' => function () {
                $card = new _01080();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::false($card instanceof IHasActions, 'no actions');
                Assert::false($card instanceof IHasManeuvers, 'no maneuvers');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(3, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($card->hasTrait('Rumor'), 'Rumor');
                Assert::true($card->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $card->getReactions(), 'one reaction');
                Assert::instanceOf(Reaction_01080::class, $card->getReactions()[0], 'Reaction_01080');
            },
        ];
    }
}
