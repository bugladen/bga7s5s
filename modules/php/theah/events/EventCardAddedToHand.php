<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardAddedToHand extends Event
{
    public int  $playerId;
    public int $cardId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;
        $this->playerId = 0;
        $this->cardId = 0;
    }

}