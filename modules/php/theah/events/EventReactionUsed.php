<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventReactionUsed extends Event
{
    public int $playerId;
    public int $ownerId;
    public string $reactionId;
    public bool $used;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->ownerId = 0;
        $this->reactionId = "";
        $this->used = false;
    }
}