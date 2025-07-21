<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardRemovedFromPlayerDiscardPile extends Event
{
    public int $cardId;
    public int  $playerId;

    public function __construct()
    {
        parent::__construct();

        $this->cardId = 0;
        $this->playerId = 0;
    }

}