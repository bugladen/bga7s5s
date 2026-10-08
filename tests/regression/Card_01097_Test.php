<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01097;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01097;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01097;

class Card_01097_Test extends TestCase
{
    public function name(): string
    {
        return '_01097 Sanjay';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Pirate Aragosta with 4/1/1/2 stats' => function () {
                $sanjay = new _01097();
                Assert::same(4, $sanjay->Resolve, 'Resolve');
                Assert::same(1, $sanjay->Combat, 'Combat');
                Assert::same(1, $sanjay->Finesse, 'Finesse');
                Assert::same(2, $sanjay->Influence, 'Influence');
                Assert::true($sanjay->hasFaction('Castille'), 'Castille');
                Assert::true($sanjay->hasTrait('Pirate'), 'Pirate');
                Assert::true($sanjay->hasTrait('Aragosta'), 'Aragosta');
            },

            'exposes exactly one Action_01097 and one Reaction_01097' => function () {
                $sanjay = new _01097();
                Assert::count(1, $sanjay->getActions(), 'one Action');
                Assert::instanceOf(Action_01097::class, $sanjay->getActions()[0], 'Action type');
                Assert::count(1, $sanjay->getReactions(), 'one Reaction');
                Assert::instanceOf(Reaction_01097::class, $sanjay->getReactions()[0], 'Reaction type');
            },

            'placed in the world, abilities are owned by Sanjay' => function () {
                $world = new TestWorld();
                $sanjay = $world->placeCharacter(new _01097(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($sanjay->Id, $sanjay->getActions()[0]->OwnerId, 'Action owner');
                Assert::same($sanjay->Id, $sanjay->getReactions()[0]->OwnerId, 'Reaction owner');
            },
        ];
    }
}
