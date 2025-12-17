<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardMoving extends Event
{
    public int $initiatingPlayerId;
    public int $cardId;
    public string $fromLocation;
    public string $toLocation;
    public bool $engage;
    public int $sourceId;

    /** @var array<int> Card IDs that have declined to cancel this movement */
    public array $cancelDeclinedByCardIds = [];

    public function __construct()
    {
        parent::__construct();
        $this->engage = true;
        $this->sourceId = 0;
        $this->cancelDeclinedByCardIds = [];
        
        $this->runEventHubAfterCards = true;
    }
}