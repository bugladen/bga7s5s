<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_01123 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Valeri Mikhailov: +1 Thrust";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventGenerateChallengeThreat && $this->Active)
        {
            $actor = $event->theah->getCharacterById($event->actorId);
            $adversary = $event->theah->getCharacterById($event->adversaryId);
            if ($actor->Wounds >= $adversary->Wounds)
            {
                $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds no Threat due to Valeri having more Wounds than opponent.");
            }
            else
            {
                $event->threat += 1;
                $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds 1 Threat due to Valeri having fewer Wounds than opponent.");
            }
        }
    }
}