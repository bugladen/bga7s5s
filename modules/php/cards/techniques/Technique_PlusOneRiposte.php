<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_PlusOneRiposte extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "+1 Riposte";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $event->riposte += 1;
            $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds 1 Riposte.");
        }        
    }
}