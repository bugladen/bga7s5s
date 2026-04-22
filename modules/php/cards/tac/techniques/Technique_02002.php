<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_02002 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("-1 Thrust");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id) 
        {
            $owner = $this->getOwningCard($event->theah);
            $event->adversaryThreat -= 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] subtracts 1 Threat."), $owner->getInjectCode(), $this->Name);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->thrust -= 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] subtracts 1 Thrust."), $owner->getInjectCode(), $this->Name);
        }
    }
}