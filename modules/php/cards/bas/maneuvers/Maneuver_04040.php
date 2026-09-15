<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_04040 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound the Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor === null || ! $actor->hasTrait("Academic"))
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary === null || $theah->game->characterIsInDiscardOrLocker($adversary))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();
            if ($adversary === null)
            {
                return;
            }

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $adversary->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $event->theah->eventCheck($woundEvent);
            $event->theah->queueEvent($woundEvent);
        }
    }
}
