<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

class EventCityCardAddedToLocation extends Event
{
    public Card $card;
    public string $location;

    public function __construct()
    {
        parent::__construct();

        $this->location = "";
        $this->priority = Event::MEDIUM_PRIORITY;
    }

}