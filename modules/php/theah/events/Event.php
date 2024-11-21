<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
abstract class Event
{
    const LOWEST_PRIORITY = 5;
    const LOW_PRIORITY = 4;
    const MEDIUM_PRIORITY = 3;
    const HIGH_PRIORITY = 2;
    const HIGHEST_PRIORITY = 1;

    public Theah $theah;
    public int $priority;

    public function __construct()
    {
        $this->priority = Event::LOWEST_PRIORITY;
    }

    public function queueEvent(Event $event)
    {
        $this->theah->queueEvent($event);
    }

}