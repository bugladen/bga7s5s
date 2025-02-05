<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateThreat;

class Technique_PlusOneThrust extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "+1 Thrust";
    }

    public function handleEvent(Card $owner, Event $event)
    { 
        parent::handleEvent($owner, $event);

        if ($event instanceof EventGenerateThreat && $this->Active) 
        {
            $event->threat += 1;
            $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds 1 Threat.");
        }
    }
}