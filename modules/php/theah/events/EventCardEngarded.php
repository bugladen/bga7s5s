<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardEngarded extends Event
{
    public int $playerId;
    public int $cardId;
    public int $sourceId;
    public string $abilityId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->sourceId = 0;
        $this->abilityId = "";
    
        $this->runEventHubAfterCards = true;
    }
 
}