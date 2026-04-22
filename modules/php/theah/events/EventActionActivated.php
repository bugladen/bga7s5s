<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventActionActivated extends Event
{
    public int $playerId;
    public int $sourceId;
    public string $actionId;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->sourceId = 0;
        $this->actionId = "";
    }
}