<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventChangeActivePlayer extends Event
{
    public int $playerId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->priority = Event::LOW_PRIORITY;
    }
}

