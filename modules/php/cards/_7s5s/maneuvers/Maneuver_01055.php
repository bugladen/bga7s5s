<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01055 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        foreach ($actor->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment && $attachment->hasTrait("Weapon") && $attachment->hasTrait("Ranged"))
            {
                return true;
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            $adversaryId = $event->theah->getDuelOpponentId($actor->Id);
            $adversary = $event->theah->getCharacterById($adversaryId);

            $owner = $this->getOwningCard($event->theah);
            $woundEvent = EventFactory::createCharacterWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->eventCheck($woundEvent);
            $event->theah->queueEvent($woundEvent);
        }
    }
}