<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
abstract class Event
{
    public Theah $theah;
    public string $transition;

    public function __construct()
    {
        $this->transition ="";
    }

    public function queueEvent(Event $event)
    {
        $this->theah->queueEvent($event);
    }

}