<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01091;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01091;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01091_Test extends TestCase
{
    public function name(): string
    {
        return '_01091 "Madre" Dolores';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Academic with Action_01091 only' => function () {
                $dolores = new _01091();
                Assert::instanceOf(IHasActions::class, $dolores, 'actions');
                Assert::false($dolores instanceof IHasReactions, 'no reactions');
                Assert::same(4, $dolores->Resolve, 'Resolve');
                Assert::same(1, $dolores->Combat, 'Combat');
                Assert::same(2, $dolores->Finesse, 'Finesse');
                Assert::same(3, $dolores->Influence, 'Influence');
                Assert::true($dolores->hasTrait('Academic'), 'Academic');
                Assert::true($dolores->hasFaction('Castille'), 'Castille');
                Assert::count(1, $dolores->getActions(), 'one action');
                Assert::instanceOf(Action_01091::class, $dolores->getActions()[0], 'Action_01091');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $dolores->getActions()[0], 'targets characters');
            },

            // WHY: Character base class carries TechniqueTrait, but Dolores prints no Technique.
            'has no printed Technique' => function () {
                $dolores = new _01091();
                Assert::instanceOf(IHasTechniques::class, $dolores, 'Character trait only');
                Assert::count(0, $dolores->getTechniques(), 'none');
            },
        ];
    }
}
