<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventPlayerLosesReknown extends Event
{
    public int $playerId;
    public int $amount;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->amount = 0;
    }
}