<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventManeuverCanceled extends Event
{
    public int $playerId;
    public string $maneuverId;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->maneuverId = "";
    }
}