<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardMoved extends Event
{
    public int $initiatingPlayerId;
    public int $cardId;
    public string $fromLocation;
    public string $toLocation;
    public bool $engage;
    public int $sourceId;
    public string $abilityId;

    public function __construct()
    {
        parent::__construct();
        $this->engage = true;
        $this->sourceId = 0;
        $this->abilityId = "";
        
        $this->runEventHubAfterCards = true;
    }
}