<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_PlusTwoThrust extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+2 Thrust");
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed
        
        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id) 
        {
            // WHY: techniqueId already identifies the activated challenge technique.
            // getOwningCharacter() is null after lethal Stiletto unequips the host —
            // do not require an attached owner (same as Technique_DestroyPlusOneThrust).
            $ownerChar = $this->getOwningCharacter($event->theah);
            if ($ownerChar === null || $ownerChar->Id == $event->actorId)
            {
                $owner = $this->getOwningCard($event->theah);
                $event->adversaryThreat += 2;
                $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds 2 Threat."), $owner->getInjectCode(), $this->Name);
            }
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id) 
        {
            $owner = $this->getOwningCard($event->theah);
            $event->thrust += 2;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds 2 Thrust."), $owner->getInjectCode(), $this->Name);
        }        
    }
}