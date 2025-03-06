<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventReknownRemovedFromCard extends Event
{
    public int $cardId;
    public int $playerId;
    public int $amount;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->cardId = 0;
        $this->playerId = 0;
        $this->amount = 0;
    }
 
}