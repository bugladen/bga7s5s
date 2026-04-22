<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventRangedAbilityPlayed extends Event
{
    public int $playerId;
    public int $sourceId;
    public string $abilityId;
    public int $performerId;
    public int $targetId;
    public string $targetLocation;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->sourceId = 0;
        $this->abilityId = "";
        $this->performerId = 0;
        $this->targetId = 0;
        $this->targetLocation = "";
    }
}