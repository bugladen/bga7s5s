<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01088;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01088;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01088;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01088_Test extends TestCase
{
    public function name(): string
    {
        return '_01088 You\'re Embarrassing Yourself';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Risk with Reaction_01088 and Maneuver_01088' => function () {
                $card = new _01088();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::false($card instanceof IHasActions, 'no actions');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(1, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Demoralize'), 'Demoralize');
                Assert::true($card->hasTrait('Valroux'), 'Valroux');
                Assert::true($card->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $card->getReactions(), 'one reaction');
                Assert::count(1, $card->getManeuvers(), 'one maneuver');
                Assert::instanceOf(Reaction_01088::class, $card->getReactions()[0], 'Reaction_01088');
                Assert::instanceOf(Maneuver_01088::class, $card->getManeuvers()[0], 'Maneuver_01088');
            },
        ];
    }
}
