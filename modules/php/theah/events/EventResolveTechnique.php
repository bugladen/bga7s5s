<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventResolveTechnique extends Event
{
    public int $playerId;
    public int $adversaryId;
    public string $techniqueId;
    public bool $inDuel;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->inDuel = true;
    }
 
}