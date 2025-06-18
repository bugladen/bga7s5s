<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventPlayerTurnEnd extends Event
{
    public $playerId;

    public function __construct()
    {        
        parent::__construct();
        
        $this->playerId = 0;
    }
}
