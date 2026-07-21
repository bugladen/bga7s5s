<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCharacterMustered extends Event
{
    public int $playerId;
    public int $characterId;
    public string $location;
    public string $fromLocation;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->characterId = 0;
        $this->location = '';
        $this->fromLocation = '';
    }
}