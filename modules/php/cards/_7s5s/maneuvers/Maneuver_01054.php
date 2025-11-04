<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGetCostForManeuverFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01054 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        $adversaryId = $theah->getDuelOpponentId($actor->Id);
        $adversary = $theah->getCharacterById($adversaryId);

        if ($theah->game->characterIsInDiscardOrLocker($adversary))
        {
            return false;
        }

        if ($adversary->ModifiedCombat > $actor->ModifiedCombat)
        {
            return false;
        }

        foreach ($actor->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment && $attachment->hasTrait("Eisenfaust"))
            {
                return true;
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelGetCostForManeuverFromHand && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            foreach ($actor->Attachments as $attachmentId)
            {
                $attachment = $event->theah->getAttachmentById($attachmentId);
                if ($attachment && $attachment->hasTrait("Eisenfaust"))
                {
                    $event->discount += 1;
                    $owner = $this->getOwningCard($event->theah);
                    $event->explanations[] = sprintf($event->theah->game->translate("%s reduces the cost of Maneuver by 1 because your participant has an Eisenfaust Attachment."), $owner->getInjectCode());
                }
            }
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $woundEvent = EventFactory::createCharacterWoundedEvent($event->adversaryId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundEvent);
        }
    }
}