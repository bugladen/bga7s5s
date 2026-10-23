<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\UtilitiesTrait;

/**
 * Runs the real UtilitiesTrait end-of-game stat writers without the rest of Game.
 *
 * WHY not a FakeGame subclass: importing UtilitiesTrait onto FakeGame replaces
 * inherited stubs whose signatures do not match the trait, and PHP fatals at load.
 * This host only supplies the collaborators those two methods actually call.
 */
class EndOfGameStatsHost
{
    use UtilitiesTrait;

    public object $bga;
    public FakeGlobals $globals;
    public TestTheah $theah;

    /** @var array<int, string> */
    public array $playerNames = [
        1 => 'Player One',
        2 => 'Player Two',
    ];

    /** @var array<string, int> */
    public array $gameStateValues = [];

    /** @var array<int, int> */
    public array $scores = [];

    /** @var list<array{0:string,1:int}> */
    public array $tableStatSets = [];

    /** @var list<array{0:string,1:int,2:int}> */
    public array $playerStatSets = [];

    private int $nextCardId = 1000;

    public function __construct()
    {
        $host = $this;
        $this->globals = new FakeGlobals();
        $this->theah = new TestTheah(new Game());
        $this->bga = new class($host) {
            public object $tableStats;
            public object $playerStats;

            public function __construct(private EndOfGameStatsHost $host)
            {
                $this->tableStats = new class($host) {
                    public function __construct(private EndOfGameStatsHost $host)
                    {
                    }

                    public function set(string $name, int $value): void
                    {
                        $this->host->tableStatSets[] = [$name, $value];
                    }
                };
                $this->playerStats = new class($host) {
                    public function __construct(private EndOfGameStatsHost $host)
                    {
                    }

                    public function set(string $name, int $value, int $playerId): void
                    {
                        $this->host->playerStatSets[] = [$name, $value, $playerId];
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

    // WHY: UtilitiesTrait::getPlayerReknown reads the player table. Route that one query here.
    public function getUniqueValueFromDB(string $sql): mixed
    {
        if (preg_match("/player_id='(\d+)'/", $sql, $matches)) {
            return $this->scores[(int) $matches[1]] ?? 0;
        }
        return 0;
    }

    public function place(Card $card, string $location, int $controllerId): Card
    {
        $card->setId(++$this->nextCardId);
        $card->ControllerId = $controllerId;
        $card->OwnerId = $controllerId;
        $card->Location = $location;
        $this->theah->addCardToWorld($card);
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
