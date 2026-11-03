<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\FrameworkActionsTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\ZombieTrait;

/**
 * StatesTraitGame + ZombieTrait (+ FrameworkActionsTrait for act* defaults the zombie
 * path may call). Exposes doZombieTurn for regression coverage of quit-player handling.
 *
 * WHY FrameworkActionsTrait too: highDrama / duel zombie cases call actHighDramaPass /
 * actBack / etc. pickDecks does not, but sharing one host keeps the suite expandable.
 */
class ZombieTraitGame extends StatesTraitGame
{
    use FrameworkActionsTrait;
    use ZombieTrait;

    public function runZombieTurn(array $state, int $playerId): void
    {
        $this->doZombieTurn($state, $playerId);
    }

    /** @return list<string> */
    public function queriesMatching(string $needle): array
    {
        return array_values(array_filter($this->dbQueries, fn(string $sql) => stripos($sql, $needle) !== false));
    }
}
