<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventReknownAddedToCard extends Event
{
    public int $cardId;
    public int $amount;

    public function __construct()
    {
        parent::__construct();

        $this->cardId = 0;
        $this->amount = 0;
        $this->priority = Event::MEDIUM_PRIORITY;
    }
}