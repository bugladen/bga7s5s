<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Action 
{
    public bool $RequiresPerformerSelected;

    public function __construct()
    {
        $this->RequiresPerformerSelected = false;
    }

    public function actFromActionPass(Game $game, int $state): void { }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  { }
    
    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void  { }

    public function stateFromAction(Game $game, int $state, string $stateName): void { }

    public function handleEvent(Event $event) { }

    public function eventCheck(Event $event) {}

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        return true;
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        return [];
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return [];
    }
}