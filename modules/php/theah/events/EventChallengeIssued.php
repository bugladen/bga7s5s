<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventChallengeIssued extends Event
{
    public int $playerId;
    public int $challengerId;
    public int $defenderId;
    public string $activatedTechniqueId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->playerId = 0;
        $this->challengerId = 0;
        $this->defenderId = 0;
        $this->activatedTechniqueId = 0;
    }
 
}