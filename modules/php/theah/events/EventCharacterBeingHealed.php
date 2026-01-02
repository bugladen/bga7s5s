<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCharacterBeingHealed extends Event
{
    public int $characterId;
    public int $sourceId;
    public int $wounds;
    public string $reason;
    public string $abilityId;

    public function __construct()
    {
        parent::__construct();

        $this->characterId = 0;
        $this->sourceId = 0;
        $this->wounds = 0;
        $this->reason = '';
        $this->abilityId = '';

        $this->runEventHubAfterCards = true;
    }
}