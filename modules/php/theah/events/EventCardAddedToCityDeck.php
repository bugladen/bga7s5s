<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventCardAddedToCityDeck extends Event
{
    public $card;
    public $playerId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::HIGH_PRIORITY;
    }
}