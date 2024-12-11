<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event; 

class EventReknownAddedToLocation extends Event
{
    public int $playerId;
    public string $location;
    public int $amount;
    public string $source;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->location = "";
        $this->amount = 0;
        $this->source = "";
        $this->priority = Event::HIGH_PRIORITY;

    }
}