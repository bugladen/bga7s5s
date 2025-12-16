<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardMoving extends Event
{
    public int $initiatingPlayerId;
    public int $cardId;
    public string $fromLocation;
    public string $toLocation;
    public bool $engage;
    public int $sourceId;

    public function __construct()
    {
        parent::__construct();
        $this->engage = true;
        $this->sourceId = 0;
        
        $this->runEventHubAfterCards = true;
    }
}