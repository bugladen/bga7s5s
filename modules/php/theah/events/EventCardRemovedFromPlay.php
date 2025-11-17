<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardRemovedFromPlay extends Event
{
    public int $playerId;
    public int $cardId;
    public string $toLocation;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->toLocation = "";
    }
}