<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class EventApproachCharacterPlayed extends Event
{
    public Character $character;
    public string $location;
    public int $playerId;
    public string $playerName;

    public function __construct()
    {
        parent::__construct();
    }

}