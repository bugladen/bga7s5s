<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventSorcererAbilityPlayed extends Event
{
    public int $playerId;
    public int $sourceId;
    public string $abilityId;
    public int $targetId;
    public string $targetLocation;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->sourceId = 0;
        $this->abilityId = "";
        $this->targetId = 0;
        $this->targetLocation = "";
    }
}