<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardMustered extends Event
{
    public int $playerId;
    public int $cardId;
    public string $location;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->location = '';
    }    
}