<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;


class EventApproachCharacterPlayed extends Event
{
    public int $playerId;
    public int $characterId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->characterId = 0;
    }
}