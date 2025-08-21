<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

abstract class Brute extends FactionCharacter
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            if (!$this->hasTrait("Brute"))
            {
                $this->addTrait("Brute");
                $this->IsUpdated = true;
            }
        }
    }
}