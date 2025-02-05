<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateThreat;

class Technique_01123 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Valeri Mikhailov: +1 Thrust";
    }

    public function handleEvent(Card $owner, Event $event)
    { 
        parent::handleEvent($owner, $event);

        if ($event instanceof EventGenerateThreat && $this->Active)
        {
            if ($event->challenger->ModifiedResolve < $event->defender->ModifiedResolve)
            {
                $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds no Threat due to Valeri having more Wounds than opponent.");
            }
            else
            {
                $event->threat += 1;
                $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds 1 Threat due to Valeri having more or equal Wounds than opponent.");
            }
        }
    }
}