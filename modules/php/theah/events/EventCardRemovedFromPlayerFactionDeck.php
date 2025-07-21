<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardRemovedFromPlayerFactionDeck extends Event
{
    public int  $playerId;
    public int $cardId;

    public function __construct()
    {
        parent::__construct();

        $this->cardId = 0;
        $this->playerId = 0;
    }

}