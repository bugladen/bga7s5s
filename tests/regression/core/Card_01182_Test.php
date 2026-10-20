<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01182;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01182;

class Card_01182_Test extends TestCase
{
    public function name(): string
    {
        return '_01182 Eko Sorridi';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable Mercenary Pirate Maghreb with Reaction_01182' => function () {
                $eko = new _01182();
                Assert::instanceOf(CityCharacter::class, $eko, 'CityCharacter');
                Assert::instanceOf(IHasReactions::class, $eko, 'reactions');
                Assert::same(3, $eko->Resolve, 'Resolve');
                Assert::same(3, $eko->Combat, 'Combat');
                Assert::same(2, $eko->Finesse, 'Finesse');
                Assert::same(1, $eko->Influence, 'Influence');
                Assert::same(4, $eko->WealthCost, 'wealth');
                Assert::same(6, $eko->CityCardNumber, 'city #');
                Assert::true($eko->Negotiable, 'Negotiable');
                Assert::true($eko->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($eko->hasTrait('Pirate'), 'Pirate');
                Assert::true($eko->hasTrait('Maghreb'), 'Maghreb');
                Assert::instanceOf(Reaction_01182::class, $eko->getReactions()[0], 'Reaction');
            },

            'owns Reaction_01182 when placed' => function () {
                $world = new TestWorld();
                $eko = $world->placeCharacter(new _01182(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $eko->getReactions(), 'one');
                Assert::same($eko->Id, $eko->getReactions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
