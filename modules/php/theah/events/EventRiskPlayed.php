<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventRiskPlayed extends Event
{
    public int $playerId;
    public int $riskId;
    
    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->riskId = 0;
    }
}