<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01118;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01118;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01118;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;

class Card_01118_Test extends TestCase
{
    public function name(): string
    {
        return '_01118 Elina Georginova';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Sorcerer with Action and Reaction' => function () {
                $elina = new _01118();
                Assert::instanceOf(Character::class, $elina, 'Character');
                Assert::instanceOf(IHasActions::class, $elina, 'actions');
                Assert::instanceOf(IHasReactions::class, $elina, 'reactions');
                Assert::same(4, $elina->Resolve, 'Resolve');
                Assert::same(2, $elina->Combat, 'Combat');
                Assert::same(2, $elina->Finesse, 'Finesse');
                Assert::same(1, $elina->Influence, 'Influence');
                Assert::true($elina->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($elina->hasTrait('Ussura'), 'Ussura trait');
                Assert::true($elina->hasFaction('Ussura'), 'Ussura faction');
                Assert::instanceOf(Action_01118::class, $elina->getActions()[0], 'Action_01118');
                Assert::instanceOf(Reaction_01118::class, $elina->getReactions()[0], 'Reaction_01118');
            },

            'ability ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $elina = $world->placeCharacter(new _01118(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($elina->Id . '_Action_01118', $elina->getActions()[0]->Id, 'action');
                Assert::same($elina->Id . '_Reaction_01118', $elina->getReactions()[0]->Id, 'reaction');
            },
        ];
    }
}
