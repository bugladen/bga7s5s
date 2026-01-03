<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventChallengeIssued extends Event
{
    public int $playerId;
    public int $challengerId;
    public int $defenderId;
    public string $activatedTechniqueId;
    public int $sourceId;
    public string $abilityId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->challengerId = 0;
        $this->defenderId = 0;
        $this->activatedTechniqueId = 0;
        $this->sourceId = 0;
        $this->abilityId = '';

        $this->runEventHubAfterCards = true;
    }
 
}