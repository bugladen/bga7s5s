<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Leader_Assassination_Test extends TestCase
{
    public function name(): string
    {
        return 'Leader_Assassination';
    }

    public function tests(): array
    {
        return [
            // WHY: 2p destroy always leaves one Leader-holder — end game, wipe loser score
            '2p leader destroy ends game with assassination' => function () {
                $world = new TestWorld();
                $world->game->playerCount = 2;
                $world->game->globals->set(Game::PLAYER_COUNT, 2);
                $world->game->playerScores = [1 => 4, 2 => 3];

                $winner = $world->placeCharacter(new GenericLeader('Winner'), Game::LOCATION_CITY_DOCKS, 1);
                $loser = $world->placeCharacter(new GenericLeader('Loser'), Game::LOCATION_CITY_FORUM, 2);

                $event = new EventCharacterDestroyed();
                $event->characterId = $loser->Id;
                $event->playerId = 1;
                $world->fireOn($loser, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'endOfGame queued');
                Assert::same('endOfGame', $transitions[0]->transition, 'transition');
                Assert::same(-1, $world->game->playerScores[2], 'loser score wiped');
                Assert::same(4, $world->game->playerScores[1], 'winner score kept');
                Assert::count(0, $world->theah->queuedOfType(EventPlayerLosesReknown::class), 'no half renown');
                unset($winner);
            },

            // WHY: 2p must end even when trait scan finds no remaining Leader (blanked /
            // already gone) — must not fall through to multiplayer half-Renown.
            '2p leader destroy ends game even with no remaining Leader trait' => function () {
                $world = new TestWorld();
                $world->game->playerCount = 2;
                $world->game->globals->set(Game::PLAYER_COUNT, 2);
                $world->game->playerScores = [1 => 4, 2 => 3];

                // Opponent has a character in play but no Leader trait
                $world->placeCharacter(new GenericCharacter('Survivor'), Game::LOCATION_CITY_DOCKS, 1);
                $loser = $world->placeCharacter(new GenericLeader('Loser'), Game::LOCATION_CITY_FORUM, 2);

                $event = new EventCharacterDestroyed();
                $event->characterId = $loser->Id;
                $event->playerId = 1;
                $world->fireOn($loser, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'endOfGame queued');
                Assert::same('endOfGame', $transitions[0]->transition, 'transition');
                Assert::same(-1, $world->game->playerScores[2], 'loser score wiped');
                Assert::count(0, $world->theah->queuedOfType(EventPlayerLosesReknown::class), 'no half renown');
            },

            // WHY: 3p with two Leaders still in play — half Renown only, game continues
            'multiplayer leader destroy halves renown when others remain' => function () {
                $world = new TestWorld();
                $world->game->playerCount = 3;
                $world->game->globals->set(Game::PLAYER_COUNT, 3);
                $world->game->playerNames[3] = 'Player Three';
                $world->game->playerScores = [1 => 5, 2 => 7, 3 => 2];

                $world->placeCharacter(new GenericLeader('A'), Game::LOCATION_CITY_DOCKS, 1);
                $victim = $world->placeCharacter(new GenericLeader('B'), Game::LOCATION_CITY_FORUM, 2);
                $world->placeCharacter(new GenericLeader('C'), Game::LOCATION_CITY_BAZAAR, 3);

                $event = new EventCharacterDestroyed();
                $event->characterId = $victim->Id;
                $event->playerId = 1;
                $world->fireOn($victim, $event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no endOfGame');
                $losses = $world->theah->queuedOfType(EventPlayerLosesReknown::class);
                Assert::count(1, $losses, 'half renown queued');
                Assert::same(2, $losses[0]->playerId, 'victim controller');
                // 7 → ceil(7/2)=4 kept → lose 3
                Assert::same(3, $losses[0]->amount, 'lose half rounded up');
            },

            // WHY: User rule — sole remaining Leader-holder wins in multiplayer too
            'multiplayer leader destroy ends game when sole leader remains' => function () {
                $world = new TestWorld();
                $world->game->playerCount = 3;
                $world->game->globals->set(Game::PLAYER_COUNT, 3);
                $world->game->playerNames[3] = 'Player Three';
                // Player 3 already lost their Leader earlier (half renown left them at 4)
                $world->game->playerScores = [1 => 3, 2 => 6, 3 => 4];

                $winner = $world->placeCharacter(new GenericLeader('Sole'), Game::LOCATION_CITY_DOCKS, 1);
                $victim = $world->placeCharacter(new GenericLeader('Last Foe'), Game::LOCATION_CITY_FORUM, 2);
                // Player 3's Leader already in locker — not in play
                $lockerLeader = new GenericLeader('Already Dead');
                $world->placeCharacter($lockerLeader, 'Locker-3', 3);

                $event = new EventCharacterDestroyed();
                $event->characterId = $victim->Id;
                $event->playerId = 1;
                $world->fireOn($victim, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'endOfGame queued');
                Assert::same('endOfGame', $transitions[0]->transition, 'transition');
                Assert::same(3, $world->game->playerScores[1], 'winner score kept');
                Assert::same(-1, $world->game->playerScores[2], 'victim wiped');
                Assert::same(-1, $world->game->playerScores[3], 'earlier loser wiped');
                Assert::count(0, $world->theah->queuedOfType(EventPlayerLosesReknown::class), 'no half on killing blow');
                unset($winner);
            },

            // WHY: Multiple Leaders under one controller still count as one player
            'two leaders same controller count as one player for assassination' => function () {
                $world = new TestWorld();
                $world->game->playerCount = 3;
                $world->game->globals->set(Game::PLAYER_COUNT, 3);
                $world->game->playerNames[3] = 'Player Three';
                $world->game->playerScores = [1 => 2, 2 => 5, 3 => 1];

                $world->placeCharacter(new GenericLeader('Primary'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericLeader('Bravos Extra'), Game::LOCATION_CITY_FORUM, 1);
                $victim = $world->placeCharacter(new GenericLeader('Foe'), Game::LOCATION_CITY_BAZAAR, 2);

                $event = new EventCharacterDestroyed();
                $event->characterId = $victim->Id;
                $event->playerId = 1;
                $world->fireOn($victim, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'sole player wins despite two leaders');
                Assert::same(-1, $world->game->playerScores[2], 'foe wiped');
            },

            // WHY: Trait gate, not instanceof — non-Leader subclass with Leader trait counts
            'non-Leader-class character with Leader trait counts for assassination' => function () {
                $world = new TestWorld();
                $world->game->playerCount = 2;
                $world->game->globals->set(Game::PLAYER_COUNT, 2);
                $world->game->playerScores = [1 => 4, 2 => 3];

                $world->placeCharacter(
                    new GenericCharacter('Trait Leader', ['Leader']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $victim = $world->placeCharacter(new GenericLeader('Loser'), Game::LOCATION_CITY_FORUM, 2);

                $event = new EventCharacterDestroyed();
                $event->characterId = $victim->Id;
                $event->playerId = 1;
                $world->fireOn($victim, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'trait Leader wins');
                Assert::same(-1, $world->game->playerScores[2], 'loser wiped');
            },
        ];
    }
}
