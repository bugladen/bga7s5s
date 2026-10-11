<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01197;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01197;

class Card_01197_Test extends TestCase
{
    public function name(): string
    {
        return '_01197 Kalla Forsberg';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable Mercenary Vesten with dashed Influence' => function () {
                $kalla = new _01197();
                Assert::instanceOf(CityCharacter::class, $kalla, 'CityCharacter');
                Assert::instanceOf(IHasActions::class, $kalla, 'actions');
                Assert::same(4, $kalla->Resolve, 'Resolve');
                Assert::same(2, $kalla->Combat, 'Combat');
                Assert::same(1, $kalla->Finesse, 'Finesse');
                Assert::same(0, $kalla->Influence, 'Influence');
                Assert::true($kalla->DashedInfluence, 'dashed Influence');
                Assert::same(4, $kalla->WealthCost, 'Wealth');
                Assert::true($kalla->Negotiable, 'Negotiable');
                Assert::true($kalla->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($kalla->hasTrait('Vesten'), 'Vesten');
                Assert::instanceOf(Action_01197::class, $kalla->getActions()[0], 'Action');
            },

            'owns Action_01197 when placed' => function () {
                $world = new TestWorld();
                $kalla = $world->placeCharacter(new _01197(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $kalla->getActions(), 'one');
                Assert::same($kalla->Id, $kalla->getActions()[0]->OwnerId, 'owner');
            },

            'state constants registered' => function () {
                Assert::same(401197, States::HIGH_DRAMA_PLAYER_TURN_01197, '01197');
                Assert::same(4011972, States::HIGH_DRAMA_PLAYER_TURN_01197_2, '01197_2');
                Assert::same(4011973, States::HIGH_DRAMA_PLAYER_TURN_01197_3, '01197_3');
            },

            // WHY (journal 2026-04-08-08): -1 when Kalla herself equips — any attachment type.
            'getEquipDiscount is -1 when Kalla is the performer' => function () {
                $world = new TestWorld();
                $kalla = $world->placeCharacter(new _01197(), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $explanations = [];

                Assert::same(1, $kalla->getEquipDiscount($world->theah, $kalla, $att, $explanations), 'discount');
                Assert::count(1, $explanations, 'explained');
            },

            'getEquipDiscount is 0 when another character equips' => function () {
                $world = new TestWorld();
                $kalla = $world->placeCharacter(new _01197(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $explanations = [];

                Assert::same(0, $kalla->getEquipDiscount($world->theah, $ally, $att, $explanations), 'no discount');
            },
        ];
    }
}
