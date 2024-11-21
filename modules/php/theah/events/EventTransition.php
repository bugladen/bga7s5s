<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventTransition extends Event
{
    public string $transition;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::HIGH_PRIORITY;
    }
}
