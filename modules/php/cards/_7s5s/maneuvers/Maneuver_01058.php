<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01058 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 or +2 Thrust");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $adversary = $event->theah->getCharacterById($event->adversaryId);
            if ($adversary->Engaged)
            {
                $event->thrust += 2;
            }
            else
            {
                $event->thrust += 1;

                $owner = $this->getOwningCard($event->theah);
                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $adversary->Id, $owner->Id, $this->Id);
                $event->theah->queueEvent($engageEvent);
            }

        }
    }
}