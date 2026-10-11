<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01199;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01199;

class Card_01199_Test extends TestCase
{
    public function name(): string
    {
        return '_01199 Takama Siad';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable Mercenary Academic Surgeon Maghreb' => function () {
                $takama = new _01199();
                Assert::instanceOf(CityCharacter::class, $takama, 'CityCharacter');
                Assert::instanceOf(IHasReactions::class, $takama, 'reactions');
                Assert::same(3, $takama->Resolve, 'Resolve');
                Assert::same(0, $takama->Combat, 'Combat');
                Assert::same(2, $takama->Finesse, 'Finesse');
                Assert::same(2, $takama->Influence, 'Influence');
                Assert::same(3, $takama->WealthCost, 'Wealth');
                Assert::true($takama->Negotiable, 'Negotiable');
                Assert::true($takama->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($takama->hasTrait('Academic'), 'Academic');
                Assert::true($takama->hasTrait('Surgeon'), 'Surgeon');
                Assert::true($takama->hasTrait('Maghreb'), 'Maghreb');
                Assert::instanceOf(Reaction_01199::class, $takama->getReactions()[0], 'Reaction');
            },

            'owns Reaction_01199 when placed' => function () {
                $world = new TestWorld();
                $takama = $world->placeCharacter(new _01199(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $takama->getReactions(), 'one');
                Assert::same($takama->Id, $takama->getReactions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
