<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventReactionTriggered extends Event
{
    public int $playerId;
    public int $performerId;
    public string $reactionId;
    public string $reactionAction;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->performerId = 0;
        $this->reactionId = "";
        $this->reactionAction = "";
    }
 
}