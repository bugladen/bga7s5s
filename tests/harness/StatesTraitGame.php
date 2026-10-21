<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\StatesTrait;

/**
 * FakeGame + the real StatesTrait. The st* state handlers under test are the production code;
 * everything they reach for outside of Theah/EventFactory (BGA Table helpers, DB reads, other
 * traits that need MySQL) is stubbed here and RECORDED so suites can assert on it.
 *
 * WHY a Game subclass instead of a fresh class: StatesTrait resolves `Game::` against its own
 * namespace, which the test autoloader maps to FakeGame. Extending FakeGame gives the trait the
 * same globals / gamestate / notify / theah the card suites already use.
 */
class StatesTraitGame extends Game
{
    use StatesTrait;

    private int $nextCardId = 1000;

    /**
     * Rows returned for `SELECT ... FROM player` (keyed by player id). Suites seed the columns
     * the state under test selects: player_name, schemeId, characterId, leaderId.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $playerRows = [];

    /** @var array<int, int> playerId => cards in hand (stDuskPhaseDiscard compares to Panache). */
    public array $handCounts = [];

    /** @var list<array{id:int}> rows currently parked in Purgatory (stDuskPhaseDiscardEvents). */
    public array $purgatoryRows = [];

    /** @var list<int> victory types passed to goToEndOfGame */
    public array $endOfGameCalls = [];

    /** @var list<int> first-player ids passed to setNewPlayerOrder */
    public array $newPlayerOrderCalls = [];

    /** @var array<int, int> */
    public array $auxScores = [];

    public int $endOfDayStatCalls = 0;
    public int $clearLastKnownCalls = 0;

    /** @var list<array{challengerId:?int,defenderId:?int}> */
    public array $clearMarkerCalls = [];

    /** @var array<int, Character> */
    public array $lastKnownCharacters = [];

    /** @var list<int> playerIds that were given extra time */
    public array $extraTimeGiven = [];

    /** @var array<int, list<Card>> playerId => cards returned by playerDrawCard (FIFO) */
    public array $drawQueue = [];

    /** @var list<int> */
    public array $drawnFor = [];

    public int $factionBatchBegins = 0;
    public int $factionBatchEnds = 0;
    public int $buildDecksCalls = 0;

    /** @var array<int, int> playerId => auto-acknowledge preference (1 = yes, 2 = no) */
    public array $autoAcknowledgePrefs = [];

    public bool $tournament = false;

    /** @var array<int, string> playerId => stored deck_source (tournament restore) */
    public array $tournamentDecks = [];

    /** @var list<array{playerId:int,key:string,value:mixed}> tournament game data written by actPickDeck */
    public array $tournamentStores = [];

    public object $cards;

    public function __construct()
    {
        parent::__construct();

        $this->theah = new TestTheah($this);

        $game = $this;
        $this->bga = new class($game) {
            public object $playerStats;
            public object $userPreferences;
            public object $tournament;

            public function __construct(private StatesTraitGame $game)
            {
                $this->playerStats = new class {
                    public function inc(string $name, int $delta = 1, ?int $playerId = null): void
                    {
                    }
                };
                $this->userPreferences = new class($game) {
                    public function __construct(private StatesTraitGame $game)
                    {
                    }

                    public function get(int $playerId, int $pref): ?int
                    {
                        return $this->game->autoAcknowledgePrefs[$playerId] ?? null;
                    }
                };
                $this->tournament = new class($game) {
                    public function __construct(private StatesTraitGame $game)
                    {
                    }

                    public function isTournament(): bool
                    {
                        return $this->game->tournament;
                    }

                    public function retrievePlayerGameData(int $playerId, string $key): ?string
                    {
                        return $this->game->tournamentDecks[$playerId] ?? null;
                    }

                    // WHY: actPickDeck stores the picked deck for tournament play; record it.
                    public function storePlayerGameData(int $playerId, string $key, mixed $value): void
                    {
                        $this->game->tournamentStores[] = ['playerId' => $playerId, 'key' => $key, 'value' => $value];
                    }
                };
            }
        };

        // WHY: stDuskPhase* read the BGA Deck through $this->cards->getCardsInLocation.
        $this->cards = new class($game) {
            public function __construct(private StatesTraitGame $game)
            {
            }

            public function getCardsInLocation($location, $locationArg = null): array
            {
                if ($location === Game::LOCATION_HAND) {
                    $rows = [];
                    $count = $this->game->handCounts[(int) $locationArg] ?? 0;
                    for ($i = 0; $i < $count; $i++) {
                        $rows[] = ['id' => 5000 + $i];
                    }
                    return $rows;
                }
                if ($location === Game::LOCATION_PURGATORY) {
                    return $this->game->purgatoryRows;
                }
                if ($location === Game::LOCATION_DUELING_LINE) {
                    return $this->game->duelingLineRows;
                }
                return [];
            }
        };
    }

    // ---- test helpers ------------------------------------------------------------------

    public function place(Card $card, string $location, int $controllerId, ?int $id = null): Card
    {
        $card->setId($id ?? ++$this->nextCardId);
        $card->ControllerId = $controllerId;
        $card->OwnerId = $controllerId;
        $card->Location = $location;
        $this->theah->addCardToWorld($card);
        $this->registerDbCard($card);
        return $card;
    }

    /** @return list<\Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event> */
    public function queued(string $class): array
    {
        return $this->theah->queuedOfType($class);
    }

    public function lastTransition(): ?string
    {
        $last = end($this->gamestate->transitions);
        return $last === false ? null : $last;
    }

    // ---- duel tables -----------------------------------------------------------------------
    // WHY: the duel states read/write the `duel` and `duel_round` tables with raw SQL. Rather than
    // run MySQL, suites seed ONE duel row and ONE duel_round row here; reads are routed by table
    // name and `UPDATE duel_round SET col = n` statements are applied to the seeded row so a state
    // that updates then re-reads (stDuelEndOfRound) sees its own writes.

    /** @var int result of SELECT MAX(duel_id) FROM duel */
    public int $duelMaxId = 0;

    /** @var array<string, int> row returned for queries against `duel` */
    public array $duelRow = [];

    /** @var array<string, int|null> row returned for queries against `duel_round` */
    public array $duelRoundRow = [];

    /** @var list<array{id:int}> rows currently in the Dueling Line */
    public array $duelingLineRows = [];

    /** @var list<array{id:int}> bottom-of-faction-deck rows (gamble reveal-from-bottom) */
    public array $bottomFactionCards = [];

    public function getUniqueValueFromDB(string $sql): mixed
    {
        if (stripos($sql, 'max(duel_id)') !== false) {
            return $this->duelMaxId ?: null;
        }
        if (preg_match('/select\s+actor_id\s+from\s+duel_round/i', $sql)) {
            return $this->duelRoundRow['actor_id'] ?? null;
        }
        if (preg_match('/select\s+gambled\s+from\s+duel_round/i', $sql)) {
            return $this->duelRoundRow['gambled'] ?? null;
        }
        return parent::getUniqueValueFromDB($sql);
    }

    public function getObjectListFromDb(string $sql): array
    {
        if (preg_match('/\bfrom\s+duel_round\b/i', $sql)) {
            return [$this->duelRoundRow];
        }
        if (preg_match('/\bfrom\s+duel\b/i', $sql)) {
            return [$this->duelRow];
        }
        return parent::getObjectListFromDb($sql);
    }

    public function DbQuery(string $sql): void
    {
        parent::DbQuery($sql);
        if (preg_match('/^\s*UPDATE\s+duel_round\s+SET\s+(.+?)\s+WHERE/is', $sql, $m)) {
            foreach (explode(',', $m[1]) as $assignment) {
                [$column, $value] = array_map('trim', explode('=', $assignment));
                $this->duelRoundRow[$column] = (int) $value;
            }
        }
    }

    public function getCardsOnBottomOfPlayerFactionDeck($playerId, int $nbr): array
    {
        return array_slice($this->bottomFactionCards, 0, $nbr);
    }

    // ---- stubs for BGA Table / other-trait collaborators -----------------------------------

    // WHY: the trait selects player rows with several column lists; tests seed $playerRows once.
    public function getCollectionFromDB(string $sql): array
    {
        if (stripos($sql, 'from player') !== false) {
            return $this->playerRows;
        }
        return parent::getCollectionFromDB($sql);
    }

    // WHY: FakeGame::getCardsOnTopOfCityDeck only peeks. In production, moving a card out of the
    // City Deck is what exposes the next one, so mirror that: a moved card leaves the stub deck.
    public function moveCardInDeck(int $cardId, string $location, $locationArg = 0): void
    {
        $this->topCityCards = array_values(array_filter(
            $this->topCityCards,
            fn(array $row) => (int) $row['id'] !== $cardId
        ));
        parent::moveCardInDeck($cardId, $location, $locationArg);
    }

    /** @var list<string> pressure stats reported as the 4th element of pressureLocation's result */
    public array $pressureStatsResult = ['Influence'];

    // WHY: FakeGame::pressureLocation returns 3 values; the real UtilitiesTrait::pressureLocation
    // returns a 4th (the stats used) which stHighDramaPressureLocation destructures and implodes.
    public function pressureLocation(int $attemptingPlayerId, ?Character $performer, string $location, string $pressureType): array
    {
        $result = parent::pressureLocation($attemptingPlayerId, $performer, $location, $pressureType);
        return [$result[0], $result[1], $result[2], $this->pressureStatsResult];
    }

    public function getPlayersNumber(): int
    {
        return count($this->playerNames);
    }

    public function getPlayerAfter(int|string $playerId): int
    {
        $ids = array_keys($this->playerNames);
        $index = array_search((int) $playerId, $ids, true);
        return $ids[($index + 1) % count($ids)];
    }

    public function setNewPlayerOrder(int $firstPlayerId): array
    {
        $this->newPlayerOrderCalls[] = $firstPlayerId;
        return ['first' => $firstPlayerId];
    }

    public function giveExtraTime(int|string $playerId): void
    {
        $this->extraTimeGiven[] = (int) $playerId;
    }

    public function goToEndOfGame(int $victoryType): void
    {
        $this->endOfGameCalls[] = $victoryType;
    }

    public function dbGetAuxScore($playerId)
    {
        return $this->auxScores[(int) $playerId] ?? 0;
    }

    public function dbSetAuxScore($playerId, $score)
    {
        $this->auxScores[(int) $playerId] = $score;
    }

    public function notifyPlayer(int $playerId, string $type, string $message, array $args = []): void
    {
        $this->notify->player($playerId, $type, $message, $args);
    }

    public function recordCharactersAtEndOfDayStat(): void
    {
        $this->endOfDayStatCalls++;
    }

    public function clearChallengeLastKnownParticipants(): void
    {
        $this->clearLastKnownCalls++;
    }

    public function clearChallengeParticipantMarkers(?int $challengerId, ?int $defenderId): void
    {
        $this->clearMarkerCalls[] = ['challengerId' => $challengerId, 'defenderId' => $defenderId];
    }

    public function getChallengeLastKnownCharacter(int $characterId): ?Character
    {
        return $this->lastKnownCharacters[$characterId] ?? null;
    }

    public function beginFactionDeckCountBatch(): void
    {
        $this->factionBatchBegins++;
    }

    public function endFactionDeckCountBatch(): void
    {
        $this->factionBatchEnds++;
    }

    public function buildDecks()
    {
        $this->buildDecksCalls++;
    }

    public function playerDrawCard($playerId): Card
    {
        $this->drawnFor[] = (int) $playerId;
        $queue = $this->drawQueue[(int) $playerId] ?? [];
        $card = array_shift($queue);
        $this->drawQueue[(int) $playerId] = $queue;
        return $card ?? new GenericCharacter('Drawn Card ' . count($this->drawnFor));
    }
}
