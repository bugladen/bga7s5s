<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardDiscardedFromPlay extends Event
{
    public int $cardId;
    public string $fromLocation;
    public int $ownerId;

    public function __construct()
    {
        parent::__construct();

        $this->ownerId = 0;
        $this->cardId = 0;
        $this->fromLocation = "";
    }
}