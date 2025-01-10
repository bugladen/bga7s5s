<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

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
        $this->priority = Event::HIGH_PRIORITY;
    }
}