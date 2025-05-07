<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventActionUsed extends Event
{
    public int $playerId;
    public int $ownerId;
    public string $actionId;
    public bool $used;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->ownerId = 0;
        $this->actionId = "";
        $this->used = false;
    }
}
