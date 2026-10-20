<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01196;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01196;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01196;

class Card_01196_Test extends TestCase
{
    public function name(): string
    {
        return '_01196 Angeline Dèmone';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable Mercenary Pirate Sorcerer Montaigne CityCharacter' => function () {
                $angeline = new _01196();
                Assert::instanceOf(CityCharacter::class, $angeline, 'CityCharacter');
                Assert::instanceOf(IHasReactions::class, $angeline, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $angeline, 'techniques');
                Assert::same(4, $angeline->Resolve, 'Resolve');
                Assert::same(3, $angeline->Combat, 'Combat');
                Assert::same(2, $angeline->Finesse, 'Finesse');
                Assert::same(2, $angeline->Influence, 'Influence');
                Assert::same(5, $angeline->WealthCost, 'Wealth');
                Assert::true($angeline->Negotiable, 'Negotiable');
                Assert::true($angeline->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($angeline->hasTrait('Pirate'), 'Pirate');
                Assert::true($angeline->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($angeline->hasTrait('Montaigne'), 'Montaigne');
            },

            // WHY: Card owns both; deep offer/calc behavior lives in Reaction_/Technique_ suites.
            'owns Reaction_01196 and Technique_01196 when placed' => function () {
                $world = new TestWorld();
                $angeline = $world->placeCharacter(new _01196(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $angeline->getReactions(), 'one reaction');
                Assert::instanceOf(Reaction_01196::class, $angeline->getReactions()[0], 'Reaction');
                Assert::same($angeline->Id, $angeline->getReactions()[0]->OwnerId, 'reaction owner');
                Assert::count(1, $angeline->getTechniques(), 'one technique');
                Assert::instanceOf(Technique_01196::class, $angeline->getTechniques()[0], 'Technique');
                Assert::same($angeline->Id, $angeline->getTechniques()[0]->OwnerId, 'technique owner');
            },
        ];
    }
}
