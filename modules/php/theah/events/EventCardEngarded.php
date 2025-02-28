<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardEngarded extends Event
{
    public int $playerId;
    public int $cardId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;
    }
 
}