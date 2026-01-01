<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardEngaged extends Event
{
    public int $cardId;
    public int $playerId;
    public int $sourceId;
    public string $abilityId;

    public function __construct()
    {
        parent::__construct();
        
        $this->cardId = 0;
        $this->playerId = 0;
        $this->sourceId = 0;
        $this->abilityId = "";
        $this->runEventHubAfterCards = true;
    }

}