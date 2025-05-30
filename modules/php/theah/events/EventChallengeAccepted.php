<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventChallengeAccepted extends Event
{
    public int $challengerId;
    public int $targetId;
    public function __construct()
    {
        parent::__construct();

        $this->challengerId = 0;
        $this->targetId = 0;
    }   
    
}