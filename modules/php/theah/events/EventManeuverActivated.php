<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventManeuverActivated extends Event
{
    public int $playerId;
    public string $maneuverId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;
    }
 
}