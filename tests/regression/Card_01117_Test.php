<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01117;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01117;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01117;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;

class Card_01117_Test extends TestCase
{
    public function name(): string
    {
        return '_01117 Ekaterina Ilyanava';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Academic Cartographer with Action and Reaction' => function () {
                $eka = new _01117();
                Assert::instanceOf(Character::class, $eka, 'Character');
                Assert::instanceOf(IHasActions::class, $eka, 'actions');
                Assert::instanceOf(IHasReactions::class, $eka, 'reactions');
                Assert::same(4, $eka->Resolve, 'Resolve');
                Assert::same(1, $eka->Combat, 'Combat');
                Assert::same(2, $eka->Finesse, 'Finesse');
                Assert::same(3, $eka->Influence, 'Influence');
                Assert::true($eka->hasTrait('Academic'), 'Academic');
                Assert::true($eka->hasTrait('Cartographer'), 'Cartographer');
                Assert::true($eka->hasTrait('Ussura'), 'Ussura trait');
                Assert::true($eka->hasFaction('Ussura'), 'Ussura faction');
                Assert::instanceOf(Action_01117::class, $eka->getActions()[0], 'Action_01117');
                Assert::instanceOf(Reaction_01117::class, $eka->getReactions()[0], 'Reaction_01117');
            },

            'ability ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $eka = $world->placeCharacter(new _01117(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($eka->Id . '_Action_01117', $eka->getActions()[0]->Id, 'action');
                Assert::same($eka->Id . '_Reaction_01117', $eka->getReactions()[0]->Id, 'reaction');
            },
        ];
    }
}
