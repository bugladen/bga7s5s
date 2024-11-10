<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
abstract class Event
{
    public Theah $theah;
    protected array $newEvents;

    public function __construct(Theah $theah)
    {
        $this->theah = $theah;
        $this->newEvents = [];
    }

    public function addNewEvent($event)
    {
        $this->newEvents[] = $event;
    }

    public function getNewEvents()
    {
        return $this->newEvents;
    }
    
}