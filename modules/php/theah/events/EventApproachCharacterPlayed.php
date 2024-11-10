<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class EventApproachCharacterPlayed extends Event
{
    public Character $character;

    public function __construct($theah)
    {
        parent::__construct($theah);
    }

}