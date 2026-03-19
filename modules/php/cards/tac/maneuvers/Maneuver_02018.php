<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_02018 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Participant: +X, X = Wounds");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();

            $woundedEvent = EventFactory::createCharacterBeingWoundedEvent($actor->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundedEvent);
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            $event->thrust += $actor->Wounds + 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Maneuver [%s] adds +%d Thrust."), $owner->getInjectCode(), $this->Name, $actor->Wounds);
        }
    }
}