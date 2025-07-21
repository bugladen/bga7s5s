<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventClaimOccuring extends Event
{
    public int $performerId;
    public string $location;
    public int $playerId;
    public Array $pressureTypes;

    public function __construct()
    {
        parent::__construct();
        
    }
}