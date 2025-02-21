<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelActionsDone extends Event
{
    public int $playerId;
    public int $actorId;
    public int $adversaryId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->actorId = 0;
        $this->adversaryId = 0;
    }
}