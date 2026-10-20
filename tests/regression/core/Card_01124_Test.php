<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;

class Card_01124_Test extends TestCase
{
    public function name(): string
    {
        return '_01124 Ved\'ma';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Sorcerer with Action and Reaction' => function () {
                $vedma = new _01124();
                Assert::instanceOf(Character::class, $vedma, 'Character');
                Assert::instanceOf(IHasActions::class, $vedma, 'actions');
                Assert::instanceOf(IHasReactions::class, $vedma, 'reactions');
                Assert::same(3, $vedma->Resolve, 'Resolve');
                Assert::same(1, $vedma->Combat, 'Combat');
                Assert::same(2, $vedma->Finesse, 'Finesse');
                Assert::same(3, $vedma->Influence, 'Influence');
                Assert::true($vedma->hasFaction('Ussura'), 'Ussura');
                Assert::true($vedma->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::instanceOf(Action_01124::class, $vedma->getActions()[0], 'Action_01124');
                Assert::instanceOf(Reaction_01124::class, $vedma->getReactions()[0], 'Reaction_01124');
            },
        ];
    }
}
