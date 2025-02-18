<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardAddedToCityDeck extends Event
{
    public $card;
    public $playerId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;
    }
}