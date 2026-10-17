<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03027a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03027b;

class Card_03027_Test extends TestCase
{
    public function name(): string
    {
        return '_03027 Odette Dubois D\'Arrent';
    }

    public function tests(): array
    {
        return [
            'constructs the Montaigne character with two city reactions' => function () {
                $odette = new _03027();
                Assert::instanceOf(Character::class, $odette, 'character');
                Assert::instanceOf(IHasReactions::class, $odette, 'reactions');
                Assert::same(3, $odette->Resolve, 'resolve');
                Assert::same(0, $odette->Combat, 'combat');
                Assert::same(3, $odette->Finesse, 'finesse');
                Assert::same(3, $odette->Influence, 'influence');
                Assert::true($odette->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($odette->hasTrait('Spy'), 'Spy');
                Assert::true($odette->hasTrait('Hero'), 'Hero');
                Assert::instanceOf(Reaction_03027a::class, $odette->getReactions()[0], 'heal');
                Assert::instanceOf(Reaction_03027b::class, $odette->getReactions()[1], 'duelist');
            },

            'owns both reactions when placed' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _03027(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($odette->Id, $odette->getReactions()[0]->OwnerId, 'heal');
                Assert::same($odette->Id, $odette->getReactions()[1]->OwnerId, 'duelist');
            },
        ];
    }
}
