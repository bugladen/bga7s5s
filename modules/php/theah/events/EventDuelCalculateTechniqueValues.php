<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelCalculateTechniqueValues extends Event
{
    public int $actorId;
    public int $adversaryId;
    public string $techniqueId;
    public int $riposte;
    public int $parry;
    public int $thrust;
    public Array $explanations;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->actorId = 0;
        $this->adversaryId = 0;
        $this->techniqueId = "";
        $this->riposte = 0;
        $this->parry = 0;
        $this->thrust = 0;
        $this->explanations = [];
        $this->runHandlerAfterCards = true;
    }
 
}