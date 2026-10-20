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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02012;

class Card_02012_Test extends TestCase
{
    public function name(): string
    {
        return '_02012 Turais Dall';
    }

    public function tests(): array
    {
        return [
            'constructs Eisen Character with Reaction and Technique' => function () {
                $turais = new _02012();
                Assert::instanceOf(Character::class, $turais, 'Character');
                Assert::instanceOf(IHasReactions::class, $turais, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $turais, 'techniques');
                Assert::same(4, $turais->Resolve, 'Resolve');
                Assert::same(3, $turais->Combat, 'Combat');
                Assert::same(1, $turais->Finesse, 'Finesse');
                Assert::same(0, $turais->Influence, 'Influence');
                Assert::true($turais->hasTrait('Hero'), 'Hero');
                Assert::true($turais->hasTrait('Berserker'), 'Berserker');
                Assert::true($turais->hasTrait('Highland Marches'), 'Highland Marches');
                Assert::true($turais->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Reaction_02012::class, $turais->getReactions()[0], 'Reaction');
                Assert::instanceOf(Technique_02012::class, $turais->getTechniques()[0], 'Technique');
            },

            'owns composites when placed' => function () {
                $world = new TestWorld();
                $turais = $world->placeCharacter(new _02012(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($turais->Id, $turais->getReactions()[0]->OwnerId, 'reaction owner');
                Assert::same($turais->Id, $turais->getTechniques()[0]->OwnerId, 'technique owner');
            },
        ];
    }
}
