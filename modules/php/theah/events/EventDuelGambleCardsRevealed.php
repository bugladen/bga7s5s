<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelGambleCardsRevealed extends Event
{
    public int $actorId;
    public int $playerId;
    public array $revealedCardIds;

    public function __construct()
    {
        parent::__construct();

        $this->actorId = 0;
        $this->playerId = 0;
        $this->revealedCardIds = [];
    }
}
