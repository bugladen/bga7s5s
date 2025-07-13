<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventTechniqueCanceled extends Event
{
    public int $playerId;
    public string $techniqueId;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->techniqueId = "";
    }
}