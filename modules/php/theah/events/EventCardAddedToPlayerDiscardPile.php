<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventCardAddedToPlayerDiscardPile extends Event
{
    public $playerId;
    public FactionDeckCard $card;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::HIGH_PRIORITY;
    }
}
