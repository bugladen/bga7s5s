<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardDiscardedFromHand extends Event
{
    public $playerId;
    public int $cardId;
    public int $sourceId; // The ID of the card that caused the discard.  Will be 0 if a framework action caused the discard.
    public bool $AsPayment;
    public bool $AsPlayed;
    public bool $asEffect; // Whether the discard is an effect or cost

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->sourceId = 0;
        $this->AsPayment = false;
        $this->AsPlayed = false;
        $this->asEffect = false;
        
        $this->runEventHubAfterCards = true;
    }
}
