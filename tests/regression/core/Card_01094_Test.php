<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01094;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01094;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class Card_01094_Test extends TestCase
{
    public function name(): string
    {
        return '_01094 "Padre" Anibal';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Academic with Action_01094' => function () {
                $anibal = new _01094();
                Assert::instanceOf(IHasActions::class, $anibal, 'actions');
                Assert::same(5, $anibal->Resolve, 'Resolve');
                Assert::same(0, $anibal->Combat, 'Combat');
                Assert::same(1, $anibal->Finesse, 'Finesse');
                Assert::same(2, $anibal->Influence, 'Influence');
                Assert::true($anibal->hasTrait('Academic'), 'Academic');
                Assert::true($anibal->hasFaction('Castille'), 'Castille');
                Assert::instanceOf(Action_01094::class, $anibal->getActions()[0], 'Action_01094');
            },

            'pressure Influence gets +2 at a location with no Renown' => function () {
                $world = new TestWorld();
                $anibal = $world->placeCharacter(new _01094(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);

                Assert::same(4, $anibal->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), '2 + 2');
            },

            // WHY: boundary - "one or fewer Renown" includes exactly 1.
            'pressure Influence gets +2 at a location with exactly one Renown' => function () {
                $world = new TestWorld();
                $anibal = $world->placeCharacter(new _01094(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                Assert::same(4, $anibal->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), '2 + 2');
            },

            'pressure Influence gets no bonus at two Renown' => function () {
                $world = new TestWorld();
                $anibal = $world->placeCharacter(new _01094(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                Assert::same(2, $anibal->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'base only');
            },

            'pressure Influence gets no bonus at a high-Renown location' => function () {
                $world = new TestWorld();
                $anibal = $world->placeCharacter(new _01094(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 5);

                Assert::same(2, $anibal->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'base only');
            },

            'bonus builds on modified Influence' => function () {
                $world = new TestWorld();
                $anibal = $world->placeCharacter(new _01094(), Game::LOCATION_CITY_DOCKS, 1);
                $anibal->ModifiedInfluence = 5;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);

                Assert::same(7, $anibal->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), '5 + 2');
            },

            'the Renown checked is the pressured location\'s' => function () {
                $world = new TestWorld();
                $anibal = $world->placeCharacter(new _01094(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 4);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 0);

                Assert::same(2, $anibal->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'Docks busy');
                Assert::same(4, $anibal->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_FORUM), 'Forum quiet');
            },

            // WHY: the passive only touches Influence pressure; Combat/Finesse/Resolve pressure stay at their base values.
            'other pressure stats are unaffected' => function () {
                $world = new TestWorld();
                $anibal = $world->placeCharacter(new _01094(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);

                Assert::same($anibal->ModifiedCombat, $anibal->getCombatPressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'Combat');
                Assert::same($anibal->ModifiedFinesse, $anibal->getFinessePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'Finesse');
                Assert::same($anibal->ModifiedResolve, $anibal->getResolvePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'Resolve');
            },
        ];
    }
}
