<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_01123 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Valeri Mikhailov: +1 Thrust or +1 Riposte";
        $this->ShortName = "+1 Thrust or +1 Riposte";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $actor = $event->theah->getCharacterById($event->actorId);
            $adversary = $event->theah->getCharacterById($event->adversaryId);
            if ($actor->Wounds < $adversary->Wounds)
            {
                $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds no Threat due to Valeri having fewer Wounds than opponent."), $this->Name);
            }
            else
            {
                $event->threat += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds 1 Threat due to Valeri having equal or more Wounds than opponent."), $this->Name);
            }
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $actor = $event->theah->getCharacterById($event->actorId);
            $adversary = $event->theah->getCharacterById($event->adversaryId);
            if ($actor->Wounds < $adversary->Wounds)
            {
                $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds +1 Riposte due to Valeri having fewer Wounds than opponent."), $this->Name);
                $event->riposte += 1;
            }
            else
            {
                $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds +1 Thrust due to Valeri having equal or more wounds than opponent."), $this->Name);
                $event->thrust += 1;
            }
        }
    }
}