<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventPlayerTakeReknownForControlledLocation extends Event
{
    public int $playerId;
    public string $location;
    public int $reknown;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->location = "";
        $this->reknown = 0;

        $this->priority = Event::MEDIUM_PRIORITY;
    }
}