<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02034;

class Card_02034_Test extends TestCase
{
    public function name(): string
    {
        return '_02034 Torvo Espada';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Hero Pirate Duelist with Action and Technique' => function () {
                $torvo = new _02034();
                Assert::instanceOf(Character::class, $torvo, 'Character');
                Assert::instanceOf(IHasActions::class, $torvo, 'actions');
                Assert::same(4, $torvo->Resolve, 'Resolve');
                Assert::same(3, $torvo->Combat, 'Combat');
                Assert::same(3, $torvo->Finesse, 'Finesse');
                Assert::same(1, $torvo->Influence, 'Influence');
                Assert::true($torvo->hasFaction('Castille'), 'Castille');
                Assert::true($torvo->hasTrait('Hero'), 'Hero');
                Assert::true($torvo->hasTrait('Pirate'), 'Pirate');
                Assert::true($torvo->hasTrait('Duelist'), 'Duelist');
                Assert::instanceOf(Action_02034::class, $torvo->getActions()[0], 'action');
                Assert::instanceOf(Technique_02034::class, $torvo->getTechniques()[0], 'technique');
            },

            'owns action and technique when placed' => function () {
                $world = new TestWorld();
                $torvo = $world->placeCharacter(new _02034(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($torvo->Id, $torvo->getActions()[0]->OwnerId, 'action');
                Assert::same($torvo->Id, $torvo->getTechniques()[0]->OwnerId, 'technique');
            },
        ];
    }
}
