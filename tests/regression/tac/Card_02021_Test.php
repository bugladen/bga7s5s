<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02021;

class Card_02021_Test extends TestCase
{
    public function name(): string
    {
        return '_02021 Grand Merchant Anghos';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Character with Reaction and Technique' => function () {
                $anghos = new _02021();
                Assert::instanceOf(Character::class, $anghos, 'Character');
                Assert::instanceOf(IHasReactions::class, $anghos, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $anghos, 'techniques');
                Assert::same(3, $anghos->Resolve, 'Resolve');
                Assert::same(2, $anghos->Combat, 'Combat');
                Assert::same(0, $anghos->Finesse, 'Finesse');
                Assert::same(3, $anghos->Influence, 'Influence');
                Assert::true($anghos->hasTrait('Dignitary'), 'Dignitary');
                Assert::true($anghos->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($anghos->hasTrait('Merchant'), 'Merchant');
                Assert::true($anghos->hasTrait('Vesten'), 'Vesten');
                Assert::true($anghos->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Reaction_02021::class, $anghos->getReactions()[0], 'Reaction');
                Assert::instanceOf(Technique_02021::class, $anghos->getTechniques()[0], 'Technique');
            },

            'owns composites when placed' => function () {
                $world = new TestWorld();
                $anghos = $world->placeCharacter(new _02021(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($anghos->Id, $anghos->getReactions()[0]->OwnerId, 'reaction owner');
                Assert::same($anghos->Id, $anghos->getTechniques()[0]->OwnerId, 'technique owner');
            },
        ];
    }
}
