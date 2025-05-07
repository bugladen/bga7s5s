<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventTechniqueUsed extends Event
{
    public int $playerId;
    public int $ownerId;
    public string $techniqueId;
    public bool $used;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->ownerId = 0;
        $this->techniqueId = "";
        $this->used = false;
    }
}