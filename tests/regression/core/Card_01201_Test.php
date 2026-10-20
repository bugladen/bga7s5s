<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01201;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01201;

class Card_01201_Test extends TestCase
{
    public function name(): string
    {
        return '_01201 Ravenna Destine';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable Mercenary Sorcerer CityCharacter with Action_01201' => function () {
                $ravenna = new _01201();
                Assert::instanceOf(CityCharacter::class, $ravenna, 'CityCharacter');
                Assert::instanceOf(IHasActions::class, $ravenna, 'actions');
                Assert::true($ravenna->Negotiable, 'Negotiable');
                Assert::same(4, $ravenna->Resolve, 'Resolve');
                Assert::same(1, $ravenna->Combat, 'Combat');
                Assert::same(2, $ravenna->Finesse, 'Finesse');
                Assert::same(1, $ravenna->Influence, 'Influence');
                Assert::same(4, $ravenna->WealthCost, 'Wealth');
                Assert::same(25, $ravenna->CityCardNumber, 'city #');
                Assert::true($ravenna->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($ravenna->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($ravenna->hasTrait('Strega'), 'Strega');
                Assert::true($ravenna->hasTrait('Vodacce'), 'Vodacce');
                Assert::instanceOf(Action_01201::class, $ravenna->getActions()[0], 'Action');
                Assert::instanceOf(ISorcererAbility::class, $ravenna->getActions()[0], 'Sorcerer ability');
            },

            'owns Action_01201 when placed' => function () {
                $world = new TestWorld();
                $ravenna = $world->placeCharacter(new _01201(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $ravenna->getActions(), 'one');
                Assert::same($ravenna->Id, $ravenna->getActions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
