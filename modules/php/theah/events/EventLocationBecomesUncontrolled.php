<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventLocationBecomesUncontrolled extends Event
{
    public int $playerId;
    public string $location;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->location = '';
    }
}