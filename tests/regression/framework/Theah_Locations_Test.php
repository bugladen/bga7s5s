<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;

/**
 * Theah: city geography, location flags, and pressure-site resolution.
 *
 * WHY: These helpers are the shared gate for claim / un-control / adjacency. Card
 * suites exercise them indirectly; this suite pins the central rules (including the
 * Discard-/Locker- guards that used to fatal via getCityLocation).
 */
class Theah_Locations_Test extends TestCase
{
    public function name(): string
    {
        return 'Theah: locations (city, adjacency, claim flags, pressure site)';
    }

    private function world(): TestWorld
    {
        return new TestWorld();
    }

    public function tests(): array
    {
        return [
            // ---- locationInCity / cardInCity / cardInPlay ------------------------------------

            'locationInCity matches the five city sites only' => function () {
                $theah = $this->world()->theah;
                Assert::true($theah->locationInCity(Game::LOCATION_CITY_DOCKS), 'docks');
                Assert::true($theah->locationInCity(Game::LOCATION_CITY_FORUM), 'forum');
                Assert::true($theah->locationInCity(Game::LOCATION_CITY_BAZAAR), 'bazaar');
                Assert::true($theah->locationInCity(Game::LOCATION_CITY_OLES_INN), "ole's");
                Assert::true($theah->locationInCity(Game::LOCATION_CITY_GOVERNORS_GARDEN), 'garden');
                Assert::false($theah->locationInCity(Game::LOCATION_PLAYER_HOME), 'home');
                Assert::false($theah->locationInCity(Game::LOCATION_HAND), 'hand');
                Assert::false($theah->locationInCity('Discard-1'), 'discard');
                Assert::false($theah->locationInCity('Locker-1'), 'locker');
            },

            'cardInCity and cardInPlay follow Location' => function () {
                $world = $this->world();
                $city = $world->placeCharacter(new GenericCharacter('City'), Game::LOCATION_CITY_FORUM, 1);
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);
                $hand = $world->placeCharacter(new GenericCharacter('Hand'), Game::LOCATION_HAND, 1);

                Assert::true($world->theah->cardInCity($city), 'city in city');
                Assert::true($world->theah->cardInPlay($city), 'city in play');
                Assert::false($world->theah->cardInCity($home), 'home not city');
                Assert::true($world->theah->cardInPlay($home), 'home in play');
                Assert::false($world->theah->cardInCity($hand), 'hand not city');
                Assert::false($world->theah->cardInPlay($hand), 'hand not in play');
            },

            // ---- adjacency / outer / non-adjacent --------------------------------------------

            'getAdjacentCityLocations for Forum is Docks + Bazaar (+ Home)' => function () {
                $theah = $this->world()->theah;
                $withHome = $theah->getAdjacentCityLocations(Game::LOCATION_CITY_FORUM, true);
                Assert::same(
                    [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR, Game::LOCATION_PLAYER_HOME],
                    $withHome,
                    'with home'
                );
                $noHome = $theah->getAdjacentCityLocations(Game::LOCATION_CITY_FORUM, false);
                Assert::same(
                    [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR],
                    $noHome,
                    'without home'
                );
            },

            'getAdjacentCityLocations expands with player count' => function () {
                $world = $this->world();
                $world->game->playerCount = 4;
                $world->game->globals->set(Game::PLAYER_COUNT, 4);

                $fromHome = $world->theah->getAdjacentCityLocations(Game::LOCATION_PLAYER_HOME, true);
                Assert::true(in_array(Game::LOCATION_CITY_OLES_INN, $fromHome, true), "ole's from home");
                Assert::true(
                    in_array(Game::LOCATION_CITY_GOVERNORS_GARDEN, $fromHome, true),
                    'garden from home'
                );

                $fromDocks = $world->theah->getAdjacentCityLocations(Game::LOCATION_CITY_DOCKS, false);
                Assert::same(
                    [Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_OLES_INN],
                    $fromDocks,
                    'docks 4p'
                );
            },

            'getOuterCityLocations depends on player count' => function () {
                $world = $this->world();
                Assert::same(
                    [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR],
                    $world->theah->getOuterCityLocations(),
                    '2p'
                );

                $world->game->playerCount = 3;
                Assert::same(
                    [Game::LOCATION_CITY_OLES_INN, Game::LOCATION_CITY_BAZAAR],
                    $world->theah->getOuterCityLocations(),
                    '3p'
                );

                $world->game->playerCount = 4;
                Assert::same(
                    [Game::LOCATION_CITY_OLES_INN, Game::LOCATION_CITY_GOVERNORS_GARDEN],
                    $world->theah->getOuterCityLocations(),
                    '4p'
                );
            },

            'getNonAdjacentCityLocations excludes self and adjacent sites' => function () {
                $world = $this->world();
                // WHY: TestTheah seeds all five city sites regardless of player count.
                $non = $world->theah->getNonAdjacentCityLocations(Game::LOCATION_CITY_FORUM);
                sort($non);
                Assert::same(
                    [
                        Game::LOCATION_CITY_GOVERNORS_GARDEN,
                        Game::LOCATION_CITY_OLES_INN,
                    ],
                    $non,
                    'forum non-adjacent (Home is not a city location entry)'
                );
            },

            'getCityLocation throws for unknown names' => function () {
                $theah = $this->world()->theah;
                Assert::throws(
                    fn() => $theah->getCityLocation('Not A Place'),
                    'not found',
                    'unknown location'
                );
            },

            'renown and controller maps round-trip CityLocation state' => function () {
                $world = $this->world();
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 4);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);

                $renown = $world->theah->getCityLocationReknown();
                Assert::same(4, $renown[Game::LOCATION_CITY_DOCKS], 'renown map');

                $controllers = $world->theah->getCityLocationControllers();
                Assert::same(2, $controllers[Game::LOCATION_CITY_DOCKS], 'controller map');
            },

            // ---- claim / un-control flags ----------------------------------------------------

            'canLocationBeClaimedBy reads CityLocation and guards non-city' => function () {
                $world = $this->world();
                Assert::true(
                    $world->theah->canLocationBeClaimedBy(1, Game::LOCATION_CITY_DOCKS),
                    'default claimable'
                );
                Assert::false(
                    $world->theah->canLocationBeClaimedBy(1, 'Discard-1'),
                    'discard must not throw'
                );
                Assert::false(
                    $world->theah->canLocationBeClaimedBy(1, Game::LOCATION_PLAYER_HOME),
                    'home not claimable via this helper'
                );
            },

            'setLocationCanBeClaimed dual-writes globals and CityLocation' => function () {
                $world = $this->world();
                $loc = Game::LOCATION_CITY_FORUM;
                $world->theah->setLocationCanBeClaimed($loc, false);

                Assert::false($world->theah->getCityLocation($loc)->CanBeClaimed, 'memory');
                Assert::false($world->game->getCanBeClaimedForLocation($loc), 'globals');
                Assert::false($world->theah->canLocationBeClaimedBy(1, $loc), 'reader');

                $world->theah->setLocationCanBeClaimed($loc, true);
                Assert::true($world->theah->canLocationBeClaimedBy(1, $loc), 'restored');
            },

            'setLocationCanBecomeUncontrolled dual-writes and guards non-city' => function () {
                $world = $this->world();
                $loc = Game::LOCATION_CITY_BAZAAR;
                Assert::true(
                    $world->theah->canLocationBecomeUncontrolledBy(1, $loc),
                    'default'
                );
                Assert::false(
                    $world->theah->canLocationBecomeUncontrolledBy(1, 'Locker-2'),
                    'locker guard'
                );

                $world->theah->setLocationCanBecomeUncontrolled($loc, false);
                Assert::false($world->theah->getCityLocation($loc)->CanBecomeUncontrolled, 'memory');
                Assert::false($world->game->getCanBecomeUncontrolledForLocation($loc), 'globals');
                Assert::false($world->theah->canLocationBecomeUncontrolledBy(1, $loc), 'reader');
            },

            // ---- resolvePressureLocation -----------------------------------------------------

            'resolvePressureLocation uses performer Location unless Basic Claim corpse' => function () {
                $world = $this->world();
                $performer = $world->placeCharacter(
                    new GenericCharacter('Pressurer'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );

                Assert::same(
                    Game::LOCATION_CITY_DOCKS,
                    $world->theah->resolvePressureLocation(
                        $performer,
                        false,
                        Game::LOCATION_CITY_FORUM
                    ),
                    'non-basic uses live Location'
                );

                Assert::same(
                    Game::LOCATION_CITY_FORUM,
                    $world->theah->resolvePressureLocation(
                        null,
                        true,
                        Game::LOCATION_CITY_FORUM
                    ),
                    'no performer → captured'
                );

                $performer->Location = 'Discard-1';
                $world->game->discardOrLockerByCardId[$performer->Id] = true;
                Assert::same(
                    Game::LOCATION_CITY_DOCKS,
                    $world->theah->resolvePressureLocation(
                        $performer,
                        true,
                        Game::LOCATION_CITY_DOCKS
                    ),
                    'basic claim keeps captured city site after death'
                );

                Assert::same(
                    'Discard-1',
                    $world->theah->resolvePressureLocation(
                        $performer,
                        false,
                        Game::LOCATION_CITY_DOCKS
                    ),
                    'non-basic still follows corpse Location'
                );
            },

            'createEvent instantiates theah\\events class by short name' => function () {
                $event = $this->world()->theah->createEvent('EventCardEngaged');
                Assert::instanceOf(EventCardEngaged::class, $event, 'class');
            },
        ];
    }
}
