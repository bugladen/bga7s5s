<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCardAddedToCityDiscardPile extends Event
{
    public int $cardId;
    public string $fromLocation;
    public int $playerId;
    public int $sourceId;
    public bool $asEffect; // Whether the discard is an effect or cost

    /** @var array<int> Card IDs that have declined to cancel this discard (Tomas _04013, etc.) */
    public array $cancelDeclinedByCardIds = [];

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->cardId = 0;
        $this->fromLocation = "";
        $this->sourceId = 0;
        $this->asEffect = false;
        $this->cancelDeclinedByCardIds = [];

        $this->runEventHubAfterCards = true;
    }
}