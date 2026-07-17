<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01061 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte, Draw Card");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Riposte."), $owner->getInjectCode());

            $actor = $event->theah->getCharacterById($event->actorId);
            $drawCard = false;
            foreach($actor->Attachments as $attachmentId)
            {
                $attachment = $event->theah->getAttachmentById($attachmentId);
                if ($attachment && $attachment->hasTrait("Weapon"))
                {
                    $drawCard = true;
                    break;
                }
            }

            if ($drawCard)
            {
                $game = $event->theah->game;
                $addEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
                $game->theah->queueEvent($addEvent);
            }
        }
        
        // EventManeuverCanceled handler not needed
    }
}