<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventRiskReactionTriggered extends Event
{
    public int $playerId;
    public int $sourceId;
    public string $internalId;
    public string $reactionId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->sourceId = 0;
        $this->internalId = "";
        $this->reactionId = "";
    }
 
}