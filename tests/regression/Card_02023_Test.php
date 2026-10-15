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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02023;

class Card_02023_Test extends TestCase
{
    public function name(): string
    {
        return '_02023 Sir Jack Harding';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Character with Action and Technique' => function () {
                $jack = new _02023();
                Assert::instanceOf(Character::class, $jack, 'Character');
                Assert::instanceOf(IHasActions::class, $jack, 'actions');
                Assert::instanceOf(IHasTechniques::class, $jack, 'techniques');
                Assert::same(4, $jack->Resolve, 'Resolve');
                Assert::same(2, $jack->Combat, 'Combat');
                Assert::same(2, $jack->Finesse, 'Finesse');
                Assert::same(2, $jack->Influence, 'Influence');
                Assert::true($jack->hasTrait('Hero'), 'Hero');
                Assert::true($jack->hasTrait('Knight'), 'Knight');
                Assert::true($jack->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($jack->hasTrait('Avalon'), 'Avalon');
                Assert::true($jack->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Action_02023::class, $jack->getActions()[0], 'Action');
                Assert::instanceOf(Technique_02023::class, $jack->getTechniques()[0], 'Technique');
            },

            'owns composites when placed' => function () {
                $world = new TestWorld();
                $jack = $world->placeCharacter(new _02023(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($jack->Id, $jack->getActions()[0]->OwnerId, 'action owner');
                Assert::same($jack->Id, $jack->getTechniques()[0]->OwnerId, 'technique owner');
            },

            'state constants registered' => function () {
                Assert::same(402023, States::HIGH_DRAMA_PLAYER_TURN_02023, 'step 1');
                Assert::same(4020232, States::HIGH_DRAMA_PLAYER_TURN_02023_2, 'step 2');
            },
        ];
    }
}
