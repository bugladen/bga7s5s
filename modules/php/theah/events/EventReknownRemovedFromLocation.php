<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventReknownRemovedFromLocation extends Event
{
    public string $location;
    public int $amount;
    public string $source;

    public function __construct()
    {
        parent::__construct();

        $this->location = "";
        $this->amount = 0;
        $this->source = "";
    }
}