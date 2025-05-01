<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardMoved extends Event
{
    public int $playerId;
    public int $cardId;
    public string $fromLocation;
    public string $toLocation;
    public bool $engage;
    public int $sourceId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;
        $this->engage = true;
        $this->sourceId = 0;
        
        $this->runHandlerAfterCards = true;
    }
}