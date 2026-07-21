<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventGambleSetup extends Event
{
    public int $actorId;
    public int $playerId;

    public function __construct()
    {
        parent::__construct();

        $this->actorId = 0;
        $this->playerId = 0;
    }
}
