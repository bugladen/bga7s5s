<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDefenderSwapped extends Event
{
    public int $playerId;
    public int $oldDefenderId;
    public int $newDefenderId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->oldDefenderId = 0;
        $this->newDefenderId = 0;
    }
}