<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventManeuverUsed extends Event
{
    public int $playerId;
    public int $ownerId;
    public string $maneuverId;
    public bool $used;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->ownerId = 0;
        $this->maneuverId = "";
        $this->used = false;
    }
}