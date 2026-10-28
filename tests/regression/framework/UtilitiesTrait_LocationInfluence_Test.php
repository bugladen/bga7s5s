<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\UtilitiesTraitHost;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05DabneyUS01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Orel;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02044;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;

/** UtilitiesTrait: standing location Influence labels and eventAffectsLocationInfluenceTotals. */
class UtilitiesTrait_LocationInfluence_Test extends TestCase
{
    public function name(): string
    {
        return 'UtilitiesTrait: location influence totals';
    }

    private function host(): UtilitiesTraitHost
    {
        return new UtilitiesTraitHost();
    }

    private function influenceFor(array $totals, string $location, int $playerId): int
    {
        foreach ($totals[$location] ?? [] as $row) {
            if ((int) $row['playerId'] === $playerId) {
                return (int) $row['influence'];
            }
        }
        throw new \RuntimeException("no row for player {$playerId} at {$location}");
    }

    public function tests(): array
    {
        return [
            'getLocationInfluenceTotalsData sums Influence pressure values per city site' => function () {
                $host = $this->host();
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 2;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 1;
                $b->resetCard();
                $host->place(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 1);

                $totals = $host->getLocationInfluenceTotalsData();
                Assert::same(2, $this->influenceFor($totals, Game::LOCATION_CITY_DOCKS, 1), 'p1 docks');
                Assert::same(1, $this->influenceFor($totals, Game::LOCATION_CITY_DOCKS, 2), 'p2 docks');
                Assert::same(1, $this->influenceFor($totals, Game::LOCATION_CITY_FORUM, 1), 'p1 forum');
                Assert::same(0, $this->influenceFor($totals, Game::LOCATION_CITY_FORUM, 2), 'p2 forum empty');
            },

            'Solomonia Forum-adjacent standing +1 is applied without PRESSURE_TYPE' => function () {
                $host = $this->host();
                $solo = $host->place(new _02044(), Game::LOCATION_CITY_FORUM, 1);
                $adj = $host->theah->getAdjacentCityLocations(Game::LOCATION_CITY_FORUM, false);
                Assert::true(count($adj) > 0, 'forum has neighbors');
                $site = $adj[0];
                $host->place(new GenericCharacter('Neighbor'), $site, 2);

                $totals = $host->getLocationInfluenceTotalsData();
                Assert::same(1, $this->influenceFor($totals, $site, 1), 'solomonia aura on adjacent');
                Assert::same(1, $this->influenceFor($totals, $site, 2), 'neighbor body');
                // WHY: Aura is adjacency-only; Forum total is just Solomonia's printed Influence.
                Assert::same(
                    (int) $solo->ModifiedInfluence,
                    $this->influenceFor($totals, Game::LOCATION_CITY_FORUM, 1),
                    'no adjacency bonus on Forum itself'
                );
            },

            'Constanzo +1 when a Thug is at the location' => function () {
                $host = $this->host();
                $leader = $host->place(new _01006(), 'Leader Zone', 1);
                $host->theah->leadersByPlayerId[1] = $leader;
                $host->place(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_BAZAAR, 1);

                $totals = $host->getLocationInfluenceTotalsData();
                // Thug Influence 1 + Constanzo standing +1
                Assert::same(2, $this->influenceFor($totals, Game::LOCATION_CITY_BAZAAR, 1), 'thug+constanzo');
            },

            'Valeri Covert +2 only on uncontrolled locations' => function () {
                $host = $this->host();
                $host->place(new _05DabneyUS01(), Game::LOCATION_CITY_DOCKS, 1);
                $host->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $totals = $host->getLocationInfluenceTotalsData();
                // Valeri body Influence (card printed) + Covert +2 — assert delta via control flip.
                $uncontrolled = $this->influenceFor($totals, Game::LOCATION_CITY_DOCKS, 1);

                $host->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                $controlled = $this->influenceFor(
                    $host->getLocationInfluenceTotalsData(),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                Assert::same($controlled + 2, $uncontrolled, 'covert +2 when uncontrolled');
            },

            'Orel standing +1 does not require uncontrolled' => function () {
                $host = $this->host();
                $host->place(new _05Orel(), Game::LOCATION_CITY_DOCKS, 1);
                $host->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);

                $with = $this->influenceFor(
                    $host->getLocationInfluenceTotalsData(),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                // Compare to a generic with same ModifiedInfluence by removing Orel class bonus:
                // Orel's printed Influence + standing +1. Flip: same board without Orel.
                $host2 = $this->host();
                $standin = $host2->place(new GenericCharacter('Standin'), Game::LOCATION_CITY_DOCKS, 1);
                $orel = new _05Orel();
                $standin->Influence = $orel->Influence;
                $standin->resetCard();
                $host2->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                $without = $this->influenceFor(
                    $host2->getLocationInfluenceTotalsData(),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                Assert::same($without + 1, $with, 'orel +1 even while controlled');
            },

            'notifyLocationInfluenceTotals emits the computed map' => function () {
                $host = $this->host();
                $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $host->notifyLocationInfluenceTotals();
                Assert::same('locationInfluenceTotalsUpdated', $host->notify->messages[0]['type'], 'type');
                Assert::true(
                    isset($host->notify->messages[0]['args']['locationInfluenceTotals'][Game::LOCATION_CITY_DOCKS]),
                    'payload'
                );
            },

            'eventAffectsLocationInfluenceTotals matches movement / claim / engage events' => function () {
                $host = $this->host();
                Assert::true($host->eventAffectsLocationInfluenceTotals(new EventCardMoved()), 'moved');
                Assert::true($host->eventAffectsLocationInfluenceTotals(new EventCardEngaged()), 'engaged');
                Assert::true($host->eventAffectsLocationInfluenceTotals(new EventCharacterDestroyed()), 'destroyed');
                Assert::true($host->eventAffectsLocationInfluenceTotals(new EventLocationClaimed()), 'claimed');
                Assert::true(
                    $host->eventAffectsLocationInfluenceTotals(new EventLocationBecomesUncontrolled()),
                    'uncontrolled'
                );
                Assert::false(
                    $host->eventAffectsLocationInfluenceTotals(new EventPlayerGainsReknown()),
                    'renown gain alone is not a location-influence event'
                );
            },
        ];
    }
}
