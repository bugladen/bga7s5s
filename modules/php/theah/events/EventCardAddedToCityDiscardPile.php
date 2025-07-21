<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardAddedToCityDiscardPile extends Event
{
    public int $cardId;
    public string $fromLocation;
    public int $playerId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
    }
}