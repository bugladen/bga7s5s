<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03040;

class Card_03040_Test extends TestCase
{
    public function name(): string
    {
        return '_03040 Soline el Gato';
    }

    public function tests(): array
    {
        return [
            'constructs a Castille thief with an action and a reaction' => function () {
                $soline = new _03040();
                Assert::instanceOf(Character::class, $soline, 'character');
                Assert::instanceOf(IHasActions::class, $soline, 'actions');
                Assert::instanceOf(IHasReactions::class, $soline, 'reactions');
                Assert::same(4, $soline->Resolve, 'resolve');
                Assert::same(2, $soline->Combat, 'combat');
                Assert::same(2, $soline->Finesse, 'finesse');
                Assert::same(2, $soline->Influence, 'influence');
                Assert::true($soline->hasFaction('Castille'), 'Castille');
                Assert::true($soline->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($soline->hasTrait('Pirate'), 'Pirate');
                Assert::true($soline->hasTrait('Thief'), 'Thief');
                Assert::instanceOf(Action_03040::class, $soline->getActions()[0], 'action');
                Assert::instanceOf(Reaction_03040::class, $soline->getReactions()[0], 'reaction');
            },
        ];
    }
}
