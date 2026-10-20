<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02002;

class Card_02002_Test extends TestCase
{
    public function name(): string
    {
        return '_02002 Elisabetta Bonora';
    }

    public function tests(): array
    {
        return [
            'constructs Vodacce Strega with Action and Technique' => function () {
                $eli = new _02002();
                Assert::instanceOf(Character::class, $eli, 'Character');
                Assert::instanceOf(IHasActions::class, $eli, 'actions');
                Assert::instanceOf(IHasTechniques::class, $eli, 'techniques');
                Assert::same(3, $eli->Resolve, 'Resolve');
                Assert::same(1, $eli->Combat, 'Combat');
                Assert::same(3, $eli->Finesse, 'Finesse');
                Assert::same(2, $eli->Influence, 'Influence');
                Assert::true($eli->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($eli->hasTrait('Strega'), 'Strega');
                Assert::true($eli->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($eli->hasFaction('Vodacce'), 'Vodacce');
                Assert::instanceOf(Action_02002::class, $eli->getActions()[0], 'Action');
                Assert::instanceOf(Technique_02002::class, $eli->getTechniques()[0], 'Technique');
            },

            'owns composites when placed' => function () {
                $world = new TestWorld();
                $eli = $world->placeCharacter(new _02002(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($eli->Id, $eli->getActions()[0]->OwnerId, 'action');
                Assert::same($eli->Id, $eli->getTechniques()[0]->OwnerId, 'technique');
            },

            'state constants registered' => function () {
                Assert::same(402002, States::HIGH_DRAMA_PLAYER_TURN_02002, '02002');
                Assert::same(4020022, States::HIGH_DRAMA_PLAYER_TURN_02002_2, '02002_2');
                Assert::same(4020023, States::HIGH_DRAMA_PLAYER_TURN_02002_3, '02002_3');
            },
        ];
    }
}
