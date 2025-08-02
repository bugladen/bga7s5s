<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardDiscardedFromHand extends Event
{
    public $playerId;
    public int $cardId;
    public bool $AsPayment;
    public bool $AsPlayed;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->AsPayment = false;
        $this->AsPlayed = false;
    }
}
