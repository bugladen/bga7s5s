<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_PlusOneThrust extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "+1 Thrust";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id) 
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner->Id == $event->actorId)
            {
                $event->adversaryThreat += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds 1 Threat."), $this->Name);
            }
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id) 
        {
            $event->thrust += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds 1 Thrust."), $this->Name);
        }        
    }
}