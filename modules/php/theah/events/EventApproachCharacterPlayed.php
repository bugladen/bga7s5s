<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class EventApproachCharacterPlayed extends Event
{
    public Character $character;
    public string $location;
    public int $playerId;

    public function __construct()
    {
        parent::__construct();
    }

}