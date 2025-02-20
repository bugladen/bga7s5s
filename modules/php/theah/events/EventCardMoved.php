<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

class EventCardMoved extends Event
{
    public Card $card;
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