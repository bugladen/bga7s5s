<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

class EventCardRemovedFromDiscardPile extends Event
{
    public Card $card;
    public int  $playerId;

    public function __construct()
    {
        parent::__construct();
    }

}