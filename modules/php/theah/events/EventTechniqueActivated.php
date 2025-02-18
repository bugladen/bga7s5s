<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventTechniqueActivated extends Event
{
    public int $playerId;
    public string $techniqueId;
    public bool $inDuel;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->inDuel = true;
    }
 
}