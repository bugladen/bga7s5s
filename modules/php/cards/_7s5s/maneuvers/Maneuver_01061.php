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

        $this->Name = clienttranslate("+1 Thrust, Draw Card");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateManeuverValues)
        {
            $event->parry += 1;

            $actor = $event->theah->getCharacterById($event->actorId);
            $drawCard = false;
            foreach($actor->Attachments as $attachmentId)
            {
                $attachment = $event->theah->getAttachmentById($attachmentId);
                if ($attachment->hasTrait("Weapon"))
                {
                    $drawCard = true;
                    break;
                }
            }

            if ($drawCard)
            {
                $owner = $this->getOwningCard($event->theah);
                $game = $event->theah->game;
                $card = $game->playerDrawCard($owner->ControllerId);
                $addEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $card, $owner->getInjectCode());
                $game->theah->queueEvent($addEvent);
            }
        }
    }
}