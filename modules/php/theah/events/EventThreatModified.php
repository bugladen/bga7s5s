<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventThreatModified extends Event
{
    public int $challengerThreat;
    public ?bool $challengerThreatIsLethal;
    public int $defenderThreat;
    public ?bool $defenderThreatIsLethal;

    public function __construct()
    {
        parent::__construct();

        $this->challengerThreat = 0;
        $this->defenderThreat = 0;
        $this->challengerThreatIsLethal = null;
        $this->defenderThreatIsLethal = null;
    }
}
