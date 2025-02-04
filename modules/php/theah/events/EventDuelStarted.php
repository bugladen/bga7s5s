<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelStarted extends Event
{
    public int $challengerId;
    public int $defenderId;

    public function __construct()
    {
        parent::__construct();

        $this->challengerId = 0;
        $this->defenderId = 0;
    }
}