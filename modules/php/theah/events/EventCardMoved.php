<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardMoved extends Event
{
    public int $cardId;
    public string $fromLocation;
    public string $toLocation;
    public int $playerId;
    public bool $Engage = true;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;
    }
}