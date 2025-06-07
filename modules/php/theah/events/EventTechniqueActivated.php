<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventTechniqueActivated extends Event
{
    public int $playerId;
    public int $ownerId;
    public string $techniqueId;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->ownerId = 0;
        $this->techniqueId = "";
    }
}
