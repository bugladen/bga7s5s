<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventActionTriggered extends Event
{
    public int $playerId;
    public int $performerId;
    public string $actionId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->performerId = 0;
        $this->actionId = "";
    }
 
}