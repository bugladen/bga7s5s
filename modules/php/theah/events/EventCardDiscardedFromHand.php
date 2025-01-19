<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventCardDiscardedFromHand extends Event
{
    public $playerId;
    public Card $card;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::HIGH_PRIORITY;
    }
}
