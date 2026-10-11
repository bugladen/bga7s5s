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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01180;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01187;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01180;

class Card_01180_Test extends TestCase
{
    public function name(): string
    {
        return '_01180 Kaj Kousei';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable Mercenary Academic Explorer Numa CityCharacter' => function () {
                $kaj = new _01180();
                Assert::instanceOf(CityCharacter::class, $kaj, 'CityCharacter');
                Assert::instanceOf(IHasActions::class, $kaj, 'actions');
                Assert::same(4, $kaj->Resolve, 'Resolve');
                Assert::same(1, $kaj->Combat, 'Combat');
                Assert::same(2, $kaj->Finesse, 'Finesse');
                Assert::same(2, $kaj->Influence, 'Influence');
                Assert::same(4, $kaj->WealthCost, 'Wealth');
                Assert::same(4, $kaj->CityCardNumber, 'city #');
                Assert::true($kaj->Negotiable, 'Negotiable');
                Assert::true($kaj->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($kaj->hasTrait('Academic'), 'Academic');
                Assert::true($kaj->hasTrait("Explorer's Society"), 'Explorer');
                Assert::true($kaj->hasTrait('Numa'), 'Numa');
                Assert::instanceOf(Action_01180::class, $kaj->getActions()[0], 'Action');
                Assert::instanceOf(CharacterAction::class, $kaj->getActions()[0], 'CharacterAction');
            },

            'owns Action_01180 when placed' => function () {
                $world = new TestWorld();
                $kaj = $world->placeCharacter(new _01180(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($kaj->Id, $kaj->getActions()[0]->OwnerId, 'owner');
            },

            'state constants registered' => function () {
                Assert::same(401180, States::HIGH_DRAMA_PLAYER_TURN_01180, 'ack');
                Assert::same(4011802, States::HIGH_DRAMA_PLAYER_TURN_01180_2, 'set player');
                Assert::same(4011803, States::HIGH_DRAMA_PLAYER_TURN_01180_3, 'choose');
                Assert::same(4011804, States::HIGH_DRAMA_PLAYER_TURN_01180_4, 'performer');
                Assert::same(4011805, States::HIGH_DRAMA_PLAYER_TURN_01180_5, 'pay');
            },

            // WHY (journal 2026-04-08-07): -1 while equipping an Artifact to any of the
            // controller's characters (not only Kaj himself).
            'getEquipDiscount is +1 for Artifact equipped by controller\'s character' => function () {
                $world = new TestWorld();
                $kaj = $world->placeCharacter(new _01180(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $artifact = $world->placeCard(new _01187(), Game::LOCATION_HAND, 1);
                $explanations = [];

                Assert::same(1, $kaj->getEquipDiscount($world->theah, $ally, $artifact, $explanations), 'discount');
                Assert::count(1, $explanations, 'explained');
            },

            'getEquipDiscount is 0 for a non-Artifact attachment' => function () {
                $world = new TestWorld();
                $kaj = $world->placeCharacter(new _01180(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $gun = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $explanations = [];

                Assert::same(0, $kaj->getEquipDiscount($world->theah, $ally, $gun, $explanations), 'no Artifact');
                Assert::count(0, $explanations, 'none');
            },

            'getEquipDiscount is 0 when an opposing character equips an Artifact' => function () {
                $world = new TestWorld();
                $kaj = $world->placeCharacter(new _01180(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $artifact = $world->placeCard(new _01187(), Game::LOCATION_HAND, 2);
                $explanations = [];

                Assert::same(0, $kaj->getEquipDiscount($world->theah, $foe, $artifact, $explanations), 'foe');
            },
        ];
    }
}
