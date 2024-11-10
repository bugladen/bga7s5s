<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

class Event
{
    protected Theah $theah;
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