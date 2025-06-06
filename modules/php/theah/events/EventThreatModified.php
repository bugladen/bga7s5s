<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventThreatModified extends Event
{
    public int $challengerId;
    public int $defenderId;
    public int $challengerThreat;
    public int $defenderThreat;

    public function __construct()
    {
        parent::__construct();

        $this->challengerId = 0;
        $this->defenderId = 0;
        $this->challengerThreat = 0;
        $this->defenderThreat = 0;
    }
}
