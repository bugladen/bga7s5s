<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelAttemptGamble extends Event
{
    public int $actorId;

    public function __construct()
    {
        parent::__construct();

        $this->actorId = 0;
    }
}
