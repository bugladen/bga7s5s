<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_PlusOneParry extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "+1 Parry";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id) 
        {
            $event->parry += 1;
            $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds 1 Parry.");
        }        
    }
}