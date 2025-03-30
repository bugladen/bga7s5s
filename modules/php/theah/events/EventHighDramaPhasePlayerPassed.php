<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventHighDramaPhasePlayerPassed extends Event
{
    public int $playerId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
    }
}