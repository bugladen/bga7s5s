<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventSchemeMovedToCity extends Event
{
    public Scheme $scheme;
    public string $location;
    public int $playerId;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::MEDIUM_PRIORITY;
    }

}