<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

class EventCardAddedToHand extends Event
{
    public Card $card;
    public int  $playerId;

    public function __construct()
    {
        parent::__construct();
    }

}