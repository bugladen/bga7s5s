<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;

class EventGenerateChallengeThreat extends Event
{
    public int $actorId;
    public int $adversaryId;
    public string $techniqueId;
    public int $actorThreat;
    public int $adversaryThreat;
    public bool $adversaryThreatIsLethal;
    public Array $explanations;
    public string $statUsed;

    public function __construct()
    {
        parent::__construct();

        $this->actorId = 0;
        $this->adversaryId = 0;
        $this->techniqueId = 0;
        $this->actorThreat = 0;
        $this->adversaryThreat = 0;
        $this->adversaryThreatIsLethal = false;
        $this->explanations = [];
        $this->statUsed = Game::STAT_COMBAT;
        $this->runEventHubAfterCards = true;
    }
 
}