<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelEnd extends Event
{
    public int $challengingPlayerId;
    public int $challengerId;
    public int $defendingPlayerId;
    public int $defenderId;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::MEDIUM_PRIORITY;

        $this->challengingPlayerId = 0;
        $this->challengerId = 0;
        $this->defendingPlayerId = 0;
        $this->defenderId = 0;
    }

}