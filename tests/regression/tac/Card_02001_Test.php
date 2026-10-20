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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02001;

class Card_02001_Test extends TestCase
{
    public function name(): string
    {
        return '_02001 Andriana Dondolo';
    }

    public function tests(): array
    {
        return [
            'constructs Vodacce Strega Character with Action and Reaction' => function () {
                $andriana = new _02001();
                Assert::instanceOf(Character::class, $andriana, 'Character');
                Assert::instanceOf(IHasActions::class, $andriana, 'actions');
                Assert::instanceOf(IHasReactions::class, $andriana, 'reactions');
                Assert::same(4, $andriana->Resolve, 'Resolve');
                Assert::same(2, $andriana->Combat, 'Combat');
                Assert::same(3, $andriana->Finesse, 'Finesse');
                Assert::same(1, $andriana->Influence, 'Influence');
                Assert::true($andriana->hasTrait('Duelist'), 'Duelist');
                Assert::true($andriana->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($andriana->hasTrait('Strega'), 'Strega');
                Assert::true($andriana->hasFaction('Vodacce'), 'Vodacce');
                Assert::instanceOf(Action_02001::class, $andriana->getActions()[0], 'Action');
                Assert::instanceOf(Reaction_02001::class, $andriana->getReactions()[0], 'Reaction');
            },

            'owns composites when placed' => function () {
                $world = new TestWorld();
                $andriana = $world->placeCharacter(new _02001(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($andriana->Id, $andriana->getActions()[0]->OwnerId, 'action owner');
                Assert::same($andriana->Id, $andriana->getReactions()[0]->OwnerId, 'reaction owner');
            },

            'state constants registered' => function () {
                Assert::same(402001, States::HIGH_DRAMA_PLAYER_TURN_02001, '02001');
                Assert::same(4020012, States::HIGH_DRAMA_PLAYER_TURN_02001_2, '02001_2');
            },
        ];
    }
}
