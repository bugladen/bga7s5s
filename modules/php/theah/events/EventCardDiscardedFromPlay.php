<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardDiscardedFromPlay extends Event
{
    public int $cardId;
    public string $fromLocation;
    public int $ownerId;
    public int $sourceId; // The ID of the card that caused the discard.  Will be 0 if a framework action caused the discard.
    public bool $asEffect; // Whether the discard is an effect or cost

    public function __construct()
    {
        parent::__construct();

        $this->ownerId = 0;
        $this->cardId = 0;
        $this->fromLocation = "";
        $this->sourceId = 0;
        $this->asEffect = false;

        $this->runEventHubAfterCards = true;
    }
}