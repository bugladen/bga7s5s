<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventCharacterRecruited extends Event
{
    public int $playerId;
    public CityCharacter $character;
    public int $discount;
    public int $cost;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::HIGH_PRIORITY;
    }
}