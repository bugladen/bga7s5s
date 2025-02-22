<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCharacterDestroyed extends Event
{
    public int $characterId;
    public string $reason;

    public function __construct()
    {
        parent::__construct();

        $this->characterId = 0;
    }
}