<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_01047 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Parry if Melee Weapon Equipped");
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id) 
        {
            $owningCharacter = $this->getOwningCharacter($event->theah);
            foreach ($owningCharacter->Attachments as $attachmentId)
            {
                $attachment = $event->theah->getAttachmentById($attachmentId);
                if ($attachment->hasTrait("Melee") && $attachment->hasTrait("Weapon"))
                {
                    $event->riposte += 1;
                    $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds 1 Riposte."), $attachment->getInjectCode(), $this->Name);
                    break;
                }
            }
        }        
    }
}