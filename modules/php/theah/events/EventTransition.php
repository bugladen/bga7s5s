<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventTransition extends Event
{
    public string $transition;
    public int $playerId;
    public int $sourceId;

    public function __construct()
    {
        parent::__construct();

        $this->transition = '';
        $this->playerId = 0;
        $this->sourceId = 0;
        $this->priority = Event::MEDIUM_PRIORITY;
    }

    public function getPlayerId(): ?int
    {
        return $this->playerId;
    }

}
