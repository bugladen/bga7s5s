<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventReactionActivated extends Event
{
    public int $playerId;
    public int $sourceId;
    public string $reactionId;

    public function __construct()
    {
        parent::__construct();
        
        $this->playerId = 0;
        $this->sourceId = 0;
        $this->reactionId = "";
    }
}