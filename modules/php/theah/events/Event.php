<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
abstract class Event
{
    public Theah $theah;
    public string $transition;
    public int $priority;

    public function __construct()
    {
        $this->transition ="";
        $this->priority = 5;
    }

    public function queueEvent(Event $event)
    {
        $this->theah->queueEvent($event);
    }

}