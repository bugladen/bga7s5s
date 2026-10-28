<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\UtilitiesTraitHost;

/**
 * UtilitiesTrait::pressureLocation — influence totals, ties, and mid-pressure modifiers.
 *
 * WHY a dedicated host: FakeGame stubs pressureLocation so card suites can inject results.
 * These tests run the real algorithm.
 */
class UtilitiesTrait_Pressure_Test extends TestCase
{
    public function name(): string
    {
        return 'UtilitiesTrait: pressureLocation';
    }

    private function host(): UtilitiesTraitHost
    {
        $host = new UtilitiesTraitHost();
        $host->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
        return $host;
    }

    /** @return array{0:bool,1:string,2:int,3:array} */
    private function pressure(
        UtilitiesTraitHost $host,
        int $attemptingPlayerId,
        ?GenericCharacter $performer,
        string $location,
        string $stat = Game::STAT_INFLUENCE
    ): array {
        return $host->pressureLocation($attemptingPlayerId, $performer, $location, $stat);
    }

    public function tests(): array
    {
        return [
            'higher Influence wins; difference is vs next highest' => function () {
                $host = $this->host();
                $a = $host->place(new GenericCharacter('A', []), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 3;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B', []), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 1;
                $b->resetCard();

                [$ok, $totals, $diff, $stats] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, 'player 1 wins');
                Assert::same(2, $diff, '3-1');
                Assert::contains('Player One:(3)', $totals, 'p1 total');
                Assert::contains('Player Two:(1)', $totals, 'p2 total');
                Assert::same([Game::STAT_INFLUENCE], $stats, 'stats');
            },

            'attempting player loses when not sole max' => function () {
                $host = $this->host();
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 1;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 2;
                $b->resetCard();

                [$ok, , $diff] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::false($ok, 'loser');
                Assert::same(1, $diff, '2-1 still reported');
            },

            'ties fail under normal pressure' => function () {
                $host = $this->host();
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 2;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 2;
                $b->resetCard();

                [$ok, , $diff] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::false($ok, 'tie fails');
                Assert::same(0, $diff, 'tie zeros difference');
            },

            'TABARD pressure wins ties for the attempting player' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::TABARD_PRESSURE_TYPE);
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 2;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 2;
                $b->resetCard();

                [$ok] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, 'tie wins');
            },

            'OVERCOAT always fails Influence pressure but still reports totals' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::OVERCOAT_PRESSURE_TYPE);
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 5;
                $a->resetCard();

                [$ok, $totals, $diff] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::false($ok, 'overcoat blocks');
                Assert::contains('Player One:(5)', $totals, 'totals still computed');
                Assert::same(5, $diff, 'solo difference');
            },

            'REPUTATION_MERITEE ignores Mercenaries at the location' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::REPUTATION_MERITEE_PRESSURE_TYPE);
                $a = $host->place(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 1;
                $a->resetCard();
                $merc = $host->place(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);
                $merc->Influence = 9;
                $merc->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, 'merc ignored so hero wins');
                Assert::contains('Player Two:(0)', $totals, 'merc contribution dropped');
            },

            'CLAUDE pressure only counts performer and en garde characters at Claude location' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE);
                $claude = $host->place(new GenericCharacter('Claude'), Game::LOCATION_CITY_DOCKS, 2);
                // WHY: Claude is only the location gate; Engaged so the filter drops his own Influence.
                $claude->Engaged = true;
                $claude->Influence = 0;
                $claude->resetCard();
                $host->globals->set(Game::CLAUD_ID, $claude->Id);

                $performer = $host->place(new GenericCharacter('P'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Influence = 1;
                $performer->Engaged = true;
                $performer->resetCard();

                $engagedOpp = $host->place(new GenericCharacter('Engaged'), Game::LOCATION_CITY_DOCKS, 2);
                $engagedOpp->Influence = 5;
                $engagedOpp->Engaged = true;
                $engagedOpp->resetCard();

                $enGardeOpp = $host->place(new GenericCharacter('EnGarde'), Game::LOCATION_CITY_DOCKS, 2);
                $enGardeOpp->Influence = 1;
                $enGardeOpp->Engaged = false;
                $enGardeOpp->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $performer, Game::LOCATION_CITY_DOCKS);
                Assert::contains('Player One:(1)', $totals, 'performer counts');
                Assert::contains('Player Two:(1)', $totals, 'only en garde opp');
                Assert::false($ok, 'tie fails under normal (Claude does not win ties)');
            },

            'LOYAL adds +1 to the chosen player' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::LOYAL_PRESSURE_TYPE);
                $host->globals->set(Game::LOYAL_PLAYER_ID, 1);
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 1;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 1;
                $b->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, 'loyal tip');
                Assert::contains('Player One:(2)', $totals, '+1 loyal');
            },

            'VANTAGE_POINT subtracts 1 from the chosen player' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::VANTAGE_POINT_PRESSURE_TYPE);
                $host->globals->set(Game::VANTAGE_POINT_PLAYER_ID, 2);
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 2;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 2;
                $b->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, 'vantage tip');
                Assert::contains('Player Two:(1)', $totals, '-1 vantage');
            },

            'PACK_TACTICS adds PRESSURE_BONUS only on Influence' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::PACK_TACTICS_PRESSURE_TYPE);
                $host->globals->set(Game::PRESSURE_BONUS, 2);
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 1;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 2;
                $b->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, 'bonus flips win');
                Assert::contains('Player One:(3)', $totals, '1+2 bonus');
            },

            'CASTILLIAN_CAPER adds +1 per Scoundrel of the attempting player' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::CASTILLIAN_CAPER_PRESSURE_TYPE);
                $a = $host->place(new GenericCharacter('Scamp', ['Scoundrel']), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 1;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 1;
                $b->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, 'scoundrel bonus');
                Assert::contains('Player One:(2)', $totals, '1+1 scoundrel');
            },

            'TRIAL_OF_FAITH adds each character wounds to their controller' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::TRIAL_OF_FAITH_PRESSURE_TYPE);
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 1;
                $a->Wounds = 2;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 2;
                $b->Wounds = 0;
                $b->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, 'wounds tip');
                Assert::contains('Player One:(3)', $totals, '1+2 wounds');
            },

            'PULL_THE_STRAND credits the chosen target to the performer controller' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::PULL_THE_STRAND_PRESSURE_TYPE);
                $performer = $host->place(new GenericCharacter('P'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Influence = 0;
                $performer->resetCard();
                $target = $host->place(new GenericCharacter('T'), Game::LOCATION_CITY_DOCKS, 2);
                $target->Influence = 3;
                $target->resetCard();
                $host->globals->set(Game::CHOSEN_TARGET, $target->Id);

                [$ok, $totals] = $this->pressure($host, 1, $performer, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, 'strand steals influence');
                Assert::contains('Player One:(3)', $totals, 'credited to performer');
                Assert::contains('Player Two:(0)', $totals, 'target controller empty');
            },

            'VALERI mid-pressure adds +2 to Valeri controller' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::VALERI_PRESSURE_TYPE);
                // WHY: Id lookup only — zero Influence so the +2 flag is the only Valeri contribution.
                $valeri = $host->place(new GenericCharacter('Valeri'), Game::LOCATION_CITY_DOCKS, 1);
                $valeri->Influence = 0;
                $valeri->resetCard();
                $host->globals->set(Game::VALERI_ID, $valeri->Id);
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 1;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 2;
                $b->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, 'valeri +2');
                Assert::contains('Player One:(3)', $totals, '1+2');
            },

            'OREL mid-pressure adds +1; OREL_WIN_TIES wins ties' => function () {
                $host = $this->host();
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::OREL_PRESSURE_TYPE);
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::OREL_WIN_TIES_PRESSURE_TYPE);
                $orel = $host->place(new GenericCharacter('Orel'), Game::LOCATION_CITY_DOCKS, 1);
                $orel->Influence = 0;
                $orel->resetCard();
                $host->globals->set(Game::OREL_ID, $orel->Id);
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 1;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 2;
                $b->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, '1+1 ties at 2 and win-ties');
                Assert::contains('Player One:(2)', $totals, 'orel +1');
            },

            'MEETING_OF_THE_MINDS counts Academics from a fresh location query' => function () {
                $host = $this->host();
                // WHY: Claude-style filters shrink $charactersAtLocation; Academic bonus must
                // still see every Academic at the site (comment in UtilitiesTrait).
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::MEETING_OF_THE_MINDS_PRESSURE_TYPE);
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::REPUTATION_MERITEE_PRESSURE_TYPE);
                $host->globals->set(Game::MEETING_OF_THE_MINDS_PLAYER_ID, 1);

                $hero = $host->place(new GenericCharacter('Hero', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $hero->Influence = 1;
                $hero->resetCard();
                // Mercenary Academic: filtered out of stat contribution, still counts for MotM.
                $mercAcademic = $host->place(
                    new GenericCharacter('MercAcad', ['Mercenary', 'Academic']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $mercAcademic->Influence = 9;
                $mercAcademic->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Influence = 2;
                $b->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $hero, Game::LOCATION_CITY_DOCKS);
                Assert::true($ok, '1 influence + 2 academics beats 2');
                Assert::contains('Player One:(3)', $totals, '1 + MotM 2');
            },

            'Combat pressure uses ModifiedCombat' => function () {
                $host = $this->host();
                $a = $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Combat = 4;
                $a->Influence = 0;
                $a->resetCard();
                $b = $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $b->Combat = 1;
                $b->resetCard();

                [$ok, $totals] = $this->pressure($host, 1, $a, Game::LOCATION_CITY_DOCKS, Game::STAT_COMBAT);
                Assert::true($ok, 'combat win');
                Assert::contains('Player One:(4)', $totals, 'combat total');
            },
        ];
    }
}
