<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\UtilitiesTrait;

/**
 * Runs the real UtilitiesTrait without loading it onto FakeGame.
 *
 * WHY not a FakeGame subclass: importing UtilitiesTrait onto FakeGame replaces
 * inherited stubs (pressureLocation, hand helpers, …) whose signatures and
 * side effects card suites rely on, and PHP fatals on some mismatches at load.
 * This host only supplies the collaborators the trait methods under test call.
 */
class UtilitiesTraitHost
{
    use UtilitiesTrait;

    public object $bga;
    public FakeGlobals $globals;
    public FakeNotify $notify;
    public FakeGamestate $gamestate;
    public TestTheah $theah;
    public object $cards;

    /** @var array<int, string> */
    public array $playerNames = [
        1 => 'Player One',
        2 => 'Player Two',
    ];

    /** @var array<int, int> playerId => turn_order */
    public array $turnOrders = [
        1 => 1,
        2 => 2,
    ];

    /** @var array<string, int> */
    public array $gameStateValues = [];

    /** @var array<int, int> */
    public array $scores = [];

    /** @var array<int, int> */
    public array $auxScores = [];

    /** @var list<array{0:string,1:int}> */
    public array $tableStatSets = [];

    /** @var list<array{0:string,1:int,2:int}> */
    public array $playerStatSets = [];

    /** @var array<int, Card> */
    public array $dbCards = [];

    /** @var list<string> */
    public array $dbQueries = [];

    /** @var array<int, int> playerId => next player in seating order */
    public array $playerAfter = [
        1 => 2,
        2 => 1,
    ];

    private int $nextCardId = 1000;

    public function __construct()
    {
        $host = $this;
        $this->globals = new FakeGlobals();
        $this->notify = new FakeNotify();
        $this->gamestate = new FakeGamestate();
        $this->theah = new TestTheah(new Game());
        $this->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);

        $this->cards = new class($host) {
            public function __construct(private UtilitiesTraitHost $host)
            {
            }

            public function getCardsInLocation(string $location, $locationArg = null): array
            {
                $rows = [];
                foreach ($this->host->dbCards as $card) {
                    if ($card->Location !== $location) {
                        continue;
                    }
                    if ($locationArg !== null && (int) $card->ControllerId !== (int) $locationArg) {
                        continue;
                    }
                    $rows[$card->Id] = ['id' => $card->Id];
                }
                return $rows;
            }
        };

        $this->bga = new class($host) {
            public object $tableStats;
            public object $playerStats;
            public object $playerScore;
            public object $playerScoreAux;

            public function __construct(private UtilitiesTraitHost $host)
            {
                $this->tableStats = new class($host) {
                    public function __construct(private UtilitiesTraitHost $host)
                    {
                    }

                    public function set(string $name, int $value): void
                    {
                        $this->host->tableStatSets[] = [$name, $value];
                    }
                };
                $this->playerStats = new class($host) {
                    public function __construct(private UtilitiesTraitHost $host)
                    {
                    }

                    public function set(string $name, int $value, int $playerId): void
                    {
                        $this->host->playerStatSets[] = [$name, $value, $playerId];
                    }
                };
                $this->playerScore = new class($host) {
                    public function __construct(private UtilitiesTraitHost $host)
                    {
                    }

                    public function set(int $playerId, int $score): void
                    {
                        $this->host->scores[$playerId] = $score;
                    }

                    public function get(int $playerId): int
                    {
                        return $this->host->scores[$playerId] ?? 0;
                    }
                };
                $this->playerScoreAux = new class($host) {
                    public function __construct(private UtilitiesTraitHost $host)
                    {
                    }

                    public function set(int $playerId, int $score): void
                    {
                        $this->host->auxScores[$playerId] = $score;
                    }

                    public function get(int $playerId): int
                    {
                        return $this->host->auxScores[$playerId] ?? 0;
                    }
                };
            }
        };
    }

    public function getGameStateValue(string $key): int
    {
        return (int) ($this->gameStateValues[$key] ?? 0);
    }

    public function loadPlayersBasicInfos(): array
    {
        $infos = [];
        foreach ($this->playerNames as $id => $name) {
            $infos[$id] = [
                'player_id' => $id,
                'player_name' => $name,
            ];
        }
        return $infos;
    }

    public function getPlayerNameById(int $playerId): string
    {
        return $this->playerNames[$playerId] ?? ('Player ' . $playerId);
    }

    public function getPlayerColorById(int $playerId): string
    {
        return sprintf('%06x', $playerId);
    }

    public function translate(string $text): string
    {
        return $text;
    }

    public function notifyAllPlayers(string $type, string $message, array $args = []): void
    {
        $this->notify->all($type, $message, $args);
    }

    public function getPlayerAfter(int $playerId): int
    {
        return $this->playerAfter[$playerId] ?? $playerId;
    }

    public function DbQuery(string $sql): void
    {
        $this->dbQueries[] = $sql;
    }

    /**
     * WHY: Trait getCardObjectFromDb hits SQL + safeUnserialize. Hand / attachment helpers
     * need RAM cards; override so those paths exercise the trait's hand logic without a DB.
     * safeUnserialize itself is tested directly.
     */
    public function getCardObjectFromDb($cardId): Card
    {
        $id = (int) $cardId;
        if (!isset($this->dbCards[$id])) {
            throw new \RuntimeException("getCardObjectFromDb: card {$id} not found");
        }
        return $this->dbCards[$id];
    }

    public function updateCardObjectInDb(?Card $card): void
    {
        if ($card !== null) {
            $this->dbCards[$card->Id] = $card;
        }
    }

    // WHY: UtilitiesTrait::getPlayerReknown / getCardIdByType / getPlayerChosenScheme read SQL.
    public function getUniqueValueFromDB(string $sql): mixed
    {
        if (preg_match("/player_id='?(\d+)'?/", $sql, $matches)) {
            return $this->scores[(int) $matches[1]] ?? 0;
        }
        return 0;
    }

    // WHY: Trait uses getCollectionFromDB and getCollectionFromDb; PHP method names are
    // case-insensitive so one implementation serves both call sites.
    public function getCollectionFromDB(string $sql): array
    {
        if (stripos($sql, 'from player') !== false) {
            $rows = [];
            asort($this->turnOrders);
            foreach ($this->turnOrders as $id => $_) {
                if (!isset($this->playerNames[$id])) {
                    continue;
                }
                $rows[$id] = ['player_id' => $id];
            }
            return $rows;
        }
        return [];
    }

    public function place(Card $card, string $location, int $controllerId): Card
    {
        $card->setId(++$this->nextCardId);
        $card->ControllerId = $controllerId;
        $card->OwnerId = $controllerId;
        $card->Location = $location;
        $this->theah->addCardToWorld($card);
        $this->dbCards[$card->Id] = $card;
        return $card;
    }

    public function playerStat(string $name, int $playerId): ?int
    {
        $value = null;
        foreach ($this->playerStatSets as [$stat, $count, $id]) {
            if ($stat === $name && $id === $playerId) {
                $value = $count;
            }
        }
        return $value;
    }
}
