<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardDrawn extends Event
{
    public int $playerId;
    public string $reason;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->reason = "";
    }

}