<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelPlayerGambled extends Event
{
    public int $playerId;
    public int $actorId;
    public int $adversaryId;
    public int $chosenCardId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->playerId = 0;
        $this->actorId = 0;
        $this->adversaryId = 0;
        $this->chosenCardId = 0;
    }
}    

