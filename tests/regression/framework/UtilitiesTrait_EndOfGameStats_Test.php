<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\EndOfGameStatsHost;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;

/** UtilitiesTrait: character counts are stored when the game ends, not only at Dusk. */
class UtilitiesTrait_EndOfGameStats_Test extends TestCase
{
    public function name(): string
    {
        return 'UtilitiesTrait: end of game character stats';
    }

    private function host(int $day): EndOfGameStatsHost
    {
        $host = new EndOfGameStatsHost();
        $host->gameStateValues[Game::DAY] = $day;
        $host->scores = [1 => 7, 2 => 4];
        return $host;
    }

    public function tests(): array
    {
        return [
            'recordEndOfGameStats writes the current day character counts' => function () {
                $host = $this->host(3);
                $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $host->place(new GenericCharacter('B'), Game::LOCATION_PLAYER_HOME, 1);
                $host->place(new GenericCharacter('C'), Game::LOCATION_CITY_FORUM, 2);
                // In hand, so not in play.
                $host->place(new GenericCharacter('Held'), Game::LOCATION_HAND, 1);

                $host->recordEndOfGameStats(Game::VICTORY_ECONOMIC);

                Assert::same(2, $host->playerStat(Game::STAT_CHARACTERS_DAY_3, 1), 'player 1 in play');
                Assert::same(1, $host->playerStat(Game::STAT_CHARACTERS_DAY_3, 2), 'player 2 in play');
                Assert::same(null, $host->playerStat(Game::STAT_CHARACTERS_DAY_1, 1), 'earlier days untouched');
                Assert::same(null, $host->playerStat(Game::STAT_CHARACTERS_DAY_2, 1), 'day 2 untouched');
                Assert::same(
                    [[Game::STAT_DAY_ENDED, 3], [Game::STAT_VICTORY_TYPE, Game::VICTORY_ECONOMIC]],
                    $host->tableStatSets,
                    'day and victory still recorded'
                );
                Assert::same(7, $host->playerStat(Game::STAT_RENOWN_ENDED, 1), 'renown');
                Assert::same(4, $host->playerStat(Game::STAT_RENOWN_ENDED, 2), 'renown');
            },

            'a second call on the same day keeps the first snapshot' => function () {
                $host = $this->host(3);
                $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $host->recordCharactersAtEndOfDayStat();

                $host->place(new GenericCharacter('Brute leaving'), Game::LOCATION_CITY_DOCKS, 1);
                $host->recordEndOfGameStats(Game::VICTORY_ASSASSINATION);

                Assert::same(1, $host->playerStat(Game::STAT_CHARACTERS_DAY_3, 1), 'dusk count kept');
                Assert::same(0, $host->playerStat(Game::STAT_CHARACTERS_DAY_3, 2), 'player 2 still written once');
            },

            'the next day is recorded after the previous day was snapshotted' => function () {
                $host = $this->host(3);
                $host->place(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $host->recordCharactersAtEndOfDayStat();

                $host->gameStateValues[Game::DAY] = 4;
                $host->place(new GenericCharacter('B'), Game::LOCATION_CITY_FORUM, 2);
                $host->recordEndOfGameStats(Game::VICTORY_DOMINANCE);

                Assert::same(1, $host->playerStat(Game::STAT_CHARACTERS_DAY_3, 1), 'day 3 kept');
                Assert::same(1, $host->playerStat(Game::STAT_CHARACTERS_DAY_4, 1), 'day 4 player 1');
                Assert::same(1, $host->playerStat(Game::STAT_CHARACTERS_DAY_4, 2), 'day 4 player 2');
            },
        ];
    }
}
