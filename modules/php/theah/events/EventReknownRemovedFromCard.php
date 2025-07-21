<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventReknownRemovedFromCard extends Event
{
    public int $playerId;
    public int $cardId;
    public int $amount;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->amount = 0;
    }
 
}