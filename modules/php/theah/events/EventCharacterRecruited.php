<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class EventCharacterRecruited extends Event
{
    public int $playerId;
    public CityCharacter $character;
    public int $discount;
    public int $cost;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::MEDIUM_PRIORITY;
    }
}