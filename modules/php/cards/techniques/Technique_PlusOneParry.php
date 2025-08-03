<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_PlusOneParry extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Parry");
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id) 
        {
            $owner = $this->getOwningCharacter($event->theah);
            $event->parry += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds 1 Parry."), $owner->getInjectCode(), $this->Name);
        }        
    }
}