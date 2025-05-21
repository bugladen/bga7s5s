<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardAddedToCityDeck extends Event
{
    public int $playerId;
    public int $cardId;
    public bool $onTop;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->playerId = 0;
        $this->cardId = 0;
        $this->onTop = true;
    }
}