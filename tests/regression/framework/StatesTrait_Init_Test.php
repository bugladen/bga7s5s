<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\StatesTraitGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTableSetup;

/** StatesTrait: multi-player init states, pick-decks init, current-player, run-events, table setup. */
class StatesTrait_Init_Test extends TestCase
{
    public function name(): string
    {
        return 'StatesTrait: init / setup states';
    }

    public function tests(): array
    {
        return [
            'stMultiPlayerInit activates every player' => function () {
                $game = new StatesTraitGame();
                $game->stMultiPlayerInit();
                Assert::same(1, $game->gamestate->allMultiactiveCalls, 'all multiactive');
                Assert::same([], $game->gamestate->nonMultiactive, 'nobody released');
            },

            'stMultiPlayerInitSansInitiatingPlayer releases the initiating player on multipleOk' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::MULTI_STATE_INITIATING_PLAYER, 2);
                $game->stMultiPlayerInitSansInitiatingPlayer();
                Assert::same(1, $game->gamestate->allMultiactiveCalls, 'all multiactive');
                Assert::same([['playerId' => 2, 'transition' => 'multipleOk']], $game->gamestate->nonMultiactive, 'initiator released');
            },

            'clearCardRevealAcknowledge releases only players whose preference is Yes (1)' => function () {
                $game = new StatesTraitGame();
                $game->gamestate->activePlayerList = [1, '2'];
                $game->autoAcknowledgePrefs = [1 => 1, 2 => 2];
                $game->clearCardRevealAcknowledgeForPlayersWithPreference();
                Assert::same([['playerId' => 1, 'transition' => 'multipleOk']], $game->gamestate->nonMultiactive, 'only pref=1 released');
            },

            'clearCardRevealAcknowledge treats a missing preference as 2 (stay active)' => function () {
                $game = new StatesTraitGame();
                $game->gamestate->activePlayerList = [1];
                $game->clearCardRevealAcknowledgeForPlayersWithPreference();
                Assert::same([], $game->gamestate->nonMultiactive, 'default keeps player active');
            },

            'stMultiPlayerInitCardRevealAcknowledge activates all then clears pref=Yes players' => function () {
                $game = new StatesTraitGame();
                $game->gamestate->activePlayerList = [1, 2];
                $game->autoAcknowledgePrefs = [1 => 2, 2 => 1];
                $game->stMultiPlayerInitCardRevealAcknowledge();
                Assert::same(1, $game->gamestate->allMultiactiveCalls, 'all multiactive');
                Assert::same([['playerId' => 2, 'transition' => 'multipleOk']], $game->gamestate->nonMultiactive, 'pref=Yes released');
            },

            'stMultiPlayerInitCardRevealAcknowledgeSansInitiatingPlayer releases initiator and pref=Yes players' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::MULTI_STATE_INITIATING_PLAYER, 1);
                $game->gamestate->activePlayerList = [2];
                $game->autoAcknowledgePrefs = [2 => 1];
                $game->stMultiPlayerInitCardRevealAcknowledgeSansInitiatingPlayer();
                Assert::same(
                    [
                        ['playerId' => 1, 'transition' => 'multipleOk'],
                        ['playerId' => 2, 'transition' => 'multipleOk'],
                    ],
                    $game->gamestate->nonMultiactive,
                    'initiator first, then pref=Yes'
                );
            },

            'stPickDecksInit outside a tournament only activates everyone' => function () {
                $game = new StatesTraitGame();
                $game->tournamentDecks = [1 => 'stored'];
                $game->stPickDecksInit();
                Assert::same(1, $game->gamestate->allMultiactiveCalls, 'all multiactive');
                Assert::same([], $game->gamestate->nonMultiactive, 'no restore outside tournament');
            },

            'stPickDecksInit in a tournament releases players with a stored deck as deckPicked' => function () {
                $game = new StatesTraitGame();
                $game->tournament = true;
                $game->tournamentDecks = [2 => 'stored-deck'];
                $game->stPickDecksInit();
                Assert::same([['playerId' => 2, 'transition' => 'deckPicked']], $game->gamestate->nonMultiactive, 'only player 2 has a deck');
            },

            'stSetCurrentPlayer makes the CURRENT_PLAYER active and continues' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::CURRENT_PLAYER, 2);
                $game->stSetCurrentPlayer();
                Assert::same([2], $game->gamestate->activePlayerChanges, 'active player');
                Assert::same([''], $game->gamestate->transitions, 'empty transition');
            },

            'stRunEvents rebuilds the city then drains events' => function () {
                $game = new StatesTraitGame();
                $game->stRunEvents();
                Assert::same(1, $game->theah->buildCityCalls, 'buildCity');
                Assert::same([false], $game->theah->runEventsCalls, 'runEvents without skipping transitions');
            },

            'stBuildDecks builds decks then continues' => function () {
                $game = new StatesTraitGame();
                $game->stBuildDecks();
                Assert::same(1, $game->buildDecksCalls, 'buildDecks');
                Assert::same([''], $game->gamestate->transitions, 'empty transition');
            },

            'stSetupTable queues a TableSetup event then continues' => function () {
                $game = new StatesTraitGame();
                $game->stSetupTable();
                Assert::count(1, $game->queued(EventTableSetup::class), 'table setup event');
                Assert::same([''], $game->gamestate->transitions, 'empty transition');
            },
        ];
    }
}
