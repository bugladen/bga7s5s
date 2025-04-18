<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventNewDay;

abstract class CityEventCard extends Card implements ICityDeckCard
{
    use CityDeckCardTrait;

    private array $playersThatUsedMeToday;

    public function __construct()
    {
        parent::__construct();

        $this->playersThatUsedMeToday = [];
    }

    public function handleEvent(Event $event)
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