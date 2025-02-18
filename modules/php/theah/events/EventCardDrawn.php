<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

class EventCardDrawn extends Event
{
    public Card $card;
    public int $playerId;
    public string $reason;

    public function __construct()
    {
        parent::__construct();

        $this->reason = "unknown reason";
    }

}