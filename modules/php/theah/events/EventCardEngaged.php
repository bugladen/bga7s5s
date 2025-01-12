<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

class EventCardEngaged extends Event
{
    public Card $card;
    public int $playerId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::HIGH_PRIORITY;
    }

}