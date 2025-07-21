<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardEngaged extends Event
{
    public int $cardId;
    public int $playerId;
    public int $sourceId;

    public function __construct()
    {
        parent::__construct();
        $this->sourceId = 0;
        
        $this->runEventHubAfterCards = true;
    }

}