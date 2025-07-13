<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelEndOfRound extends Event
{
    public int $playerId;
    public int $actorId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->actorId = 0;
    }
}