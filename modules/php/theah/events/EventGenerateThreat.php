<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class EventGenerateThreat extends Event
{
    public Character $challenger;
    public Character $defender;
    public int $threat;
    public Array $explanations;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;
        $this->threat = 0;
        $this->explanations = [];
        $this->runHandlerAfterCards = true;
    }
 
}