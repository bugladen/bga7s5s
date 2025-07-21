<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class EventResolveScheme extends Event
{
    public Scheme $scheme;
    public int $playerId;
    public string $playerName;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->playerName = "";

        $this->priority = Event::LOW_PRIORITY;
    }
}