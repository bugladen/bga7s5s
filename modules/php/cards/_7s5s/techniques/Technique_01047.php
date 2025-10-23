<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01047 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Parry if Melee Weapon Equipped");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;
        
        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (!$inDuel)
            return false;

        $owningCharacter = $this->getOwningCharacter($theah);
        foreach ($owningCharacter->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment && $attachment->hasTrait("Melee") && $attachment->hasTrait("Weapon"))
            {
                return true;
            }
        }

        return false;
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
                if ($attachment && $attachment->hasTrait("Melee") && $attachment->hasTrait("Weapon"))
                {
                    $event->riposte += 1;
                    $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds 1 Riposte."), $attachment->getInjectCode(), $this->Name);
                    break;
                }
            }
        }        
    }
}