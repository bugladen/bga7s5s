<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01204;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05William;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05William;

class Card_05William_Test extends TestCase
{
    public function name(): string
    {
        return '_05William Syrneth Secrets';
    }

    public function tests(): array
    {
        return [
            'constructs Discovery Scholarship scheme with City Action' => function () {
                $scheme = new _05William();
                Assert::same('Syrneth Secrets', $scheme->Name, 'name');
                Assert::same(27, $scheme->Initiative, 'initiative');
                Assert::true($scheme->hasTrait('Discovery'), 'Discovery');
                Assert::true($scheme->hasTrait('Scholarship'), 'Scholarship');
                Assert::true(str_contains($scheme->Text, 'at -1 cost'), 'printed -1 cost');
                Assert::instanceOf(Action_05William::class, $scheme->getActions()[0], 'City Action');
                Assert::false($scheme->PlanningEquipDiscountActive, 'discount disarmed');
            },

            // WHY: v5 text "at -1 cost" is resolve-equip only — not a standing Kaj aura.
            'getEquipDiscount is 0 while planning discount is disarmed' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _05William(), Game::LOCATION_PLAYER_HOME, 1);
                $academic = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $artifact = $world->placeCard(new _01204(), Game::LOCATION_CITY_DOCKS, 0);
                $explanations = [];

                Assert::same(0, $scheme->getEquipDiscount($world->theah, $academic, $artifact, $explanations), 'no discount');
                Assert::count(0, $explanations, 'none');
            },

            'getEquipDiscount is +1 for Artifact while planning discount is armed' => function () {
                $world = new TestWorld();
                /** @var _05William $scheme */
                $scheme = $world->placeCard(new _05William(), Game::LOCATION_PLAYER_HOME, 1);
                $academic = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $artifact = $world->placeCard(new _01204(), Game::LOCATION_CITY_DOCKS, 0);
                $scheme->PlanningEquipDiscountActive = true;
                $explanations = [];

                Assert::same(1, $scheme->getEquipDiscount($world->theah, $academic, $artifact, $explanations), 'discount');
                Assert::count(1, $explanations, 'explained');
            },

            'getEquipDiscount stays 0 for non-Artifact even when armed' => function () {
                $world = new TestWorld();
                /** @var _05William $scheme */
                $scheme = $world->placeCard(new _05William(), Game::LOCATION_PLAYER_HOME, 1);
                $academic = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $gun = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 0);
                $scheme->PlanningEquipDiscountActive = true;
                $explanations = [];

                Assert::same(0, $scheme->getEquipDiscount($world->theah, $academic, $gun, $explanations), 'not Artifact');
            },

            // WHY: Printed -1 is applied in getPlanningEquipDiscount even before the pay-flag
            // is armed — location-chosen affordability runs with the flag still false.
            'getEquippableArtifactsAtLocation includes cost-2 Artifact at 1 Wealth while disarmed' => function () {
                $world = new TestWorld();
                $world->game->handWealthOverride = 1;
                /** @var _05William $scheme */
                $scheme = $world->placeCard(new _05William(), Game::LOCATION_PLAYER_HOME, 1);
                $academic = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $hand = $world->placeCard(new _01204(), Game::LOCATION_CITY_DOCKS, 0);
                Assert::same(2, $hand->WealthCost, 'Syrneth Hand costs 2');
                Assert::false($scheme->PlanningEquipDiscountActive, 'flag still off');

                $ids = array_map(fn($a) => $a->Id, $scheme->getEquippableArtifactsAtLocation(
                    $world->theah,
                    $academic,
                    Game::LOCATION_CITY_DOCKS
                ));
                Assert::true(in_array($hand->Id, $ids, true), 'affordable at -1');
            },

            // WHY: Turn-1 scheme resolve is before Planning draw — empty hand. Cost 1 − 1 = 0.
            'getEquippableArtifactsAtLocation includes cost-1 Artifact at 0 Wealth' => function () {
                $world = new TestWorld();
                $world->game->handWealthOverride = 0;
                /** @var _05William $scheme */
                $scheme = $world->placeCard(new _05William(), Game::LOCATION_PLAYER_HOME, 1);
                $academic = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $eye = $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01200(), Game::LOCATION_CITY_DOCKS, 0);
                Assert::same(1, $eye->WealthCost, 'Crystal Eye costs 1');

                $ids = array_map(fn($a) => $a->Id, $scheme->getEquippableArtifactsAtLocation(
                    $world->theah,
                    $academic,
                    Game::LOCATION_CITY_DOCKS
                ));
                Assert::true(in_array($eye->Id, $ids, true), 'free after -1');
            },

            'getEquippableArtifactsAtLocation excludes cost-2 Artifact at 0 Wealth' => function () {
                $world = new TestWorld();
                $world->game->handWealthOverride = 0;
                /** @var _05William $scheme */
                $scheme = $world->placeCard(new _05William(), Game::LOCATION_PLAYER_HOME, 1);
                $academic = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $hand = $world->placeCard(new _01204(), Game::LOCATION_CITY_DOCKS, 0);

                $ids = array_map(fn($a) => $a->Id, $scheme->getEquippableArtifactsAtLocation(
                    $world->theah,
                    $academic,
                    Game::LOCATION_CITY_DOCKS
                ));
                Assert::false(in_array($hand->Id, $ids, true), 'still costs 1');
            },
        ];
    }
}
