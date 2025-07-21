<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCityCardAddedToLocation extends Event
{
    public int $cardId;
    public string $location;

    public function __construct()
    {
        parent::__construct();

        $this->cardId = 0;
        $this->location = "";
    }

}