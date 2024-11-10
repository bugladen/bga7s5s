<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventCityCardAddedToLocation extends Event
{
    public CityDeckCard $card;
    public string $location;

    public function __construct($theah)
    {
        parent::__construct($theah);
    }

}