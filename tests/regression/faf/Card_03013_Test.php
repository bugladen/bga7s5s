<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03013a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03013;

class Card_03013_Test extends TestCase
{
    public function name(): string
    {
        return '_03013 Daniella Dietrich';
    }

    public function tests(): array
    {
        return [
            'constructs the Eisen leader with an Action, two Reactions, and a Technique' => function () {
                $daniella = new _03013();
                Assert::instanceOf(Leader::class, $daniella, 'leader');
                Assert::instanceOf(IHasActions::class, $daniella, 'actions');
                Assert::instanceOf(IHasReactions::class, $daniella, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $daniella, 'techniques');
                Assert::same(6, $daniella->Resolve, 'resolve');
                Assert::same(2, $daniella->Combat, 'combat');
                Assert::same(2, $daniella->Finesse, 'finesse');
                Assert::same(3, $daniella->Influence, 'influence');
                Assert::same(5, $daniella->CrewCap, 'crew');
                Assert::same(7, $daniella->Panache, 'panache');
                Assert::true($daniella->hasFaction('Eisen'), 'Eisen');
                Assert::true($daniella->hasTrait('Strega'), 'Strega');
                Assert::true($daniella->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($daniella->hasTrait('Leader'), 'Leader');
                Assert::instanceOf(Action_03013::class, $daniella->getActions()[0], 'action');
                Assert::instanceOf(Reaction_03013::class, $daniella->getReactions()[0], 'discount');
                Assert::instanceOf(Reaction_03013a::class, $daniella->getReactions()[1], 'grant');
                Assert::instanceOf(Technique_03013::class, $daniella->getTechniques()[0], 'technique');
            },

            'owns every composite when placed' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($daniella->Id, $daniella->getActions()[0]->OwnerId, 'action');
                Assert::same($daniella->Id, $daniella->getReactions()[0]->OwnerId, 'discount');
                Assert::same($daniella->Id, $daniella->getReactions()[1]->OwnerId, 'grant');
                Assert::same($daniella->Id, $daniella->getTechniques()[0]->OwnerId, 'technique');
            },
        ];
    }
}
