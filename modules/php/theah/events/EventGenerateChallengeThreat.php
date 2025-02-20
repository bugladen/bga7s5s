<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;

class EventGenerateChallengeThreat extends Event
{
    public int $actorId;
    public int $adversaryId;
    public int $threat;
    public Array $explanations;
    public string $statUsed;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->actorId = 0;
        $this->adversaryId = 0;
        $this->threat = 0;
        $this->explanations = [];
        $this->statUsed = Game::CHALLENGE_STAT_COMBAT;
        $this->runHandlerAfterCards = true;
    }
 
}