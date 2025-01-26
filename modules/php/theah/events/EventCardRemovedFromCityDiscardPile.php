<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventCardRemovedFromCityDiscardPile extends Event
{
    public $card;
    public $playerId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;
    }
}