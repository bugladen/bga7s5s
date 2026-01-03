<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;

class Maneuver_01129 extends Maneuver
{
    public bool $IsActive = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Maneuvers and Techniques not usable for the rest of the Duel");
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventManeuverActivated && $this->IsActive)
        {
            $owner = $this->getOwningCard($event->theah);
            throw new \BgaUserException(sprintf($event->theah->game->translate("You cannot activate Maneuvers while %s is active."), $owner->getInjectCode()));
        }

        if ($event instanceof EventTechniqueActivated && $this->IsActive)
        {
            $owner = $this->getOwningCard($event->theah);
            throw new \BgaUserException(sprintf($event->theah->game->translate("You cannot activate Techniques while %s is active."), $owner->getInjectCode()));
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->IsActive = true;
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEnd && $this->IsActive)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->IsActive = false;
            $owner->IsUpdated = true;
        }
    }
}