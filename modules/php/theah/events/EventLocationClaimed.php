<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventLocationClaimed extends Event
{
    public int $playerId;
    public int $performerId;
    public string $location;

    public function __construct()
    {
        parent::__construct();
    }
 
}