<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardEngarded extends Event
{
    public int $playerId;
    public int $cardId;
    public int $sourceId;
    public string $abilityId;
    // WHY: stRecruitUndoEngage refunds a provisional Parley engage via Engarded.
    // That is UI cost undo, not a game-effect En Garde — Forced "when would / after
    // en garde" cards (Fate's Burden, Legion's Caress) must skip it. Continuous
    // sync (Eventail +1 Inf while en garde) still needs the event.
    public bool $asCostUndo;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->sourceId = 0;
        $this->abilityId = "";
        $this->asCostUndo = false;
    
        $this->runEventHubAfterCards = true;
    }
 
}