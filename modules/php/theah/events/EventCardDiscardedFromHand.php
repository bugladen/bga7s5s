<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardDiscardedFromHand extends Event
{
    public $playerId;
    public int $cardId;

    public function __construct()
    {
        parent::__construct();

    }
}
