<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01203;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01203;

class Card_01203_Test extends TestCase
{
    public function name(): string
    {
        return '_01203 Leja Juska';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable Mercenary Duelist CityCharacter with Reaction_01203' => function () {
                $leja = new _01203();
                Assert::instanceOf(CityCharacter::class, $leja, 'CityCharacter');
                Assert::instanceOf(IHasReactions::class, $leja, 'reactions');
                Assert::true($leja->Negotiable, 'Negotiable');
                Assert::same(4, $leja->Resolve, 'Resolve');
                Assert::same(2, $leja->Combat, 'Combat');
                Assert::same(3, $leja->Finesse, 'Finesse');
                Assert::same(1, $leja->Influence, 'Influence');
                Assert::same(5, $leja->WealthCost, 'Wealth');
                Assert::same(27, $leja->CityCardNumber, 'city #');
                Assert::true($leja->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($leja->hasTrait('Duelist'), 'Duelist');
                Assert::true($leja->hasTrait('Sarmatian'), 'Sarmatian');
                Assert::instanceOf(Reaction_01203::class, $leja->getReactions()[0], 'Reaction');
            },

            'owns Reaction_01203 when placed' => function () {
                $world = new TestWorld();
                $leja = $world->placeCharacter(new _01203(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $leja->getReactions(), 'one');
                Assert::same($leja->Id, $leja->getReactions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
