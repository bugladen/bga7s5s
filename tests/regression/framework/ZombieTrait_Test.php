<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\ZombieTraitGame;

/**
 * ZombieTrait: quit-player defaults that must leave the table in a playable state.
 */
class ZombieTrait_Test extends TestCase
{
    public function name(): string
    {
        return 'ZombieTrait: pickDecks and related multi-active defaults';
    }

    public function tests(): array
    {
        return [
            // Production: table 939315286 — zombie pickDecks released without deck_source,
            // then buildDecks fatals on $deck->faction (null json_decode).
            'pickDecks zombie assigns a random Core starter deck then releases the player' => function () {
                $game = new ZombieTraitGame();

                $game->runZombieTurn([
                    'type' => 'multipleactiveplayer',
                    'name' => 'pickDecks',
                ], 2);

                $updates = $game->queriesMatching('UPDATE player SET deck_source');
                Assert::count(1, $updates, 'deck_source written');
                Assert::contains("WHERE player_id='2'", $updates[0], 'for the zombie');

                Assert::true((bool) preg_match("/deck_source = '(.*)' WHERE/", $updates[0], $m), 'sql shape');
                $deck = json_decode(stripslashes($m[1]));
                Assert::true(is_object($deck), 'decodes');
                Assert::same('Core', $deck->set, 'Core set only');
                Assert::true(is_string($deck->id) && str_starts_with($deck->id, 'SD'), 'starter id');
                Assert::true(is_string($deck->faction) && $deck->faction !== '', 'faction for buildDecks');

                Assert::same([['playerId' => 2, 'transition' => 'deckPicked']], $game->gamestate->nonMultiactive, 'released');
                Assert::same([], $game->tournamentStores, 'not a tournament');
            },

            'pickDecks zombie in a tournament also locks the assigned deck' => function () {
                $game = new ZombieTraitGame();
                $game->tournament = true;

                $game->runZombieTurn([
                    'type' => 'multipleactiveplayer',
                    'name' => 'pickDecks',
                ], 1);

                Assert::count(1, $game->tournamentStores, 'stored once');
                $store = $game->tournamentStores[0];
                Assert::same(1, $store['playerId'], 'player');
                Assert::same('deck_source', $store['key'], 'key');
                Assert::true(is_object($store['value']), 'decoded deck object');
                Assert::same('Core', $store['value']->set, 'Core set only');
                Assert::true(is_string($store['value']->id) && str_starts_with($store['value']->id, 'SD'), 'starter id');
                Assert::same([['playerId' => 1, 'transition' => 'deckPicked']], $game->gamestate->nonMultiactive, 'released');
            },
        ];
    }
}
