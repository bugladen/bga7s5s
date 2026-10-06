<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01011_Test extends TestCase
{
    public function name(): string
    {
        return '_01011 Servo Scarpa';
    }

    public function tests(): array
    {
        return [
            'constructs with Action_01011, Technique_01011, and stats' => function () {
                $servo = new _01011();
                Assert::instanceOf(IHasActions::class, $servo, 'has actions');
                Assert::instanceOf(IHasTechniques::class, $servo, 'has techniques');
                Assert::same(5, $servo->Resolve, 'Resolve');
                Assert::same(3, $servo->Combat, 'Combat');
                Assert::same(2, $servo->Finesse, 'Finesse');
                Assert::same(1, $servo->Influence, 'Influence');
                // WHY regression: audit 2026-04-13 fixed typo "Deulist"
                Assert::true($servo->hasTrait('Duelist'), 'Duelist spelled correctly');
                Assert::true($servo->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($servo->hasTrait('Vodacce'), 'Vodacce');
                Assert::true($servo->hasFaction('Vodacce'), 'Faction');
                Assert::instanceOf(Action_01011::class, $servo->getActions()[0], 'Action_01011');
                Assert::instanceOf(Technique_01011::class, $servo->getTechniques()[0], 'Technique_01011');
            },

            'state constant registered' => function () {
                Assert::same(401011, States::HIGH_DRAMA_PLAYER_TURN_01011, 'state id');
            },
        ];
    }
}
