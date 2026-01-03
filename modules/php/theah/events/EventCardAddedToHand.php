<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardAddedToHand extends Event
{
    public int  $playerId;
    public int $cardId;
    public bool $hidden;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->hidden = false;
    }

}