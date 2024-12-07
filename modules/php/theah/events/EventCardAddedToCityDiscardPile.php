<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventCardAddedToCityDiscardPile extends Event
{
    public CityDeckCard $card;
    public string $fromLocation;
    public int $playerId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::HIGH_PRIORITY;
    }
}