<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCharacterWounded extends Event
{
    public int $characterId;
    public int $sourceId;
    public string $abilityId;
    public int $wounds;
    public string $reason;

    public function __construct()
    {
        parent::__construct();

        $this->characterId = 0;
        $this->sourceId = 0;
        $this->abilityId = '';
        $this->wounds = 0;
        $this->reason = '';

    }
}