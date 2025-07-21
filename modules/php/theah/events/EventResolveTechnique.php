<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

// Note: Use EventDuelCalculateTechniqueValues if you want to modify duel round stats.
class EventResolveTechnique extends Event
{
    public int $playerId;
    public int $actorId;
    public int $adversaryId;
    public string $techniqueId;
    public bool $inDuel;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->actorId = 0;
        $this->adversaryId = 0;
        $this->techniqueId = 0;
        $this->inDuel = true;
    }
 
}