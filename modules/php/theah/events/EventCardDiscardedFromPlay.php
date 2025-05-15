<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardDiscardedFromPlay extends Event
{
    public int $cardId;
    public string $fromLocation;
    public int $playerId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->playerId = 0;
        $this->cardId = 0;
        $this->fromLocation = "";
    }
}