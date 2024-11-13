<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventNewDay;

abstract class Event extends CityDeckCard
{
    private array $playersThatUsedMeToday;
    
    public function __construct()
    {
        parent::__construct();

        $this->playersThatUsedMeToday = [];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);
        
        if ($event instanceof EventNewDay) {
            $this->playersThatUsedMeToday = [];
        }
    }

    public function getPropertyArray() : array
    {
        $properties = parent::getPropertyArray();

        $properties['type'] = 'Event';

        return $properties;
    }


}