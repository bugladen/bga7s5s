<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

class EventCardDiscardedFromHand extends Event
{
    public $playerId;
    public Card $card;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::MEDIUM_PRIORITY;
    }
}
