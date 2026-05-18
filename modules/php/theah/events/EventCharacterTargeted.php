<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

// Sent when a character is targeted by an ability.  Used in edge-cases when manipulation events aren't feasible.
// Use-cases: Defending Honor
class EventCharacterTargeted extends Event
{
    public int $playerId;
    public int $targetId;
    public int $sourceId;
    public string $abilityId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->targetId = 0;
        $this->sourceId = 0;
        $this->abilityId = '';
    }
}