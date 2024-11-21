<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event; 

class EventReknownAddedToLocation extends Event
{
    public string $location;
    public int $amount;

    public function __construct()
    {
        parent::__construct();
    }
}