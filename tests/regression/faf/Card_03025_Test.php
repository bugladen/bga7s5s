<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03025a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03025b;

class Card_03025_Test extends TestCase
{
    public function name(): string
    {
        return '_03025 Angeline Dèmone, Prodigal Capitaine';
    }

    public function tests(): array
    {
        return [
            'constructs the Montaigne leader with a reaction and two techniques' => function () {
                $angeline = new _03025();
                Assert::instanceOf(Leader::class, $angeline, 'leader');
                Assert::instanceOf(IHasReactions::class, $angeline, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $angeline, 'techniques');
                Assert::same(9, $angeline->Resolve, 'resolve');
                Assert::same(3, $angeline->Combat, 'combat');
                Assert::same(3, $angeline->Finesse, 'finesse');
                Assert::same(1, $angeline->Influence, 'influence');
                Assert::same(5, $angeline->CrewCap, 'crew');
                Assert::same(6, $angeline->Panache, 'panache');
                Assert::true($angeline->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($angeline->hasTrait('Duelist'), 'Duelist');
                Assert::true($angeline->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($angeline->hasTrait('Pirate'), 'Pirate');
                Assert::instanceOf(Reaction_03025::class, $angeline->getReactions()[0], 'reaction');
                Assert::instanceOf(Technique_03025a::class, $angeline->getTechniques()[0], 'riposte');
                Assert::instanceOf(Technique_03025b::class, $angeline->getTechniques()[1], 'move');
            },

            'owns the composites when placed' => function () {
                $world = new TestWorld();
                $angeline = $world->placeCharacter(new _03025(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($angeline->Id, $angeline->getReactions()[0]->OwnerId, 'reaction');
                Assert::same($angeline->Id, $angeline->getTechniques()[0]->OwnerId, 'riposte');
                Assert::same($angeline->Id, $angeline->getTechniques()[1]->OwnerId, 'move');
            },
        ];
    }
}
