<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class EventLocationClaimed extends Event
{
    public Character $performer;
    public string $location;
    public int $playerId;

    public function __construct()
    {
        parent::__construct();
    }
 
}