<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventSchemeCardRevealed extends Event
{
    public int $schemeId;
    public string $playerId;
    public string $location;
    public string $playerName;

    public function __construct()
    {
        parent::__construct();

        $this->schemeId = 0;
        $this->playerId = 0;
        $this->location = "";
        $this->playerName = "";
    }
}