<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventNewDay extends Event
{
    public int $dayNumber;

    public function __construct()
    {
        parent::__construct();
    }
}