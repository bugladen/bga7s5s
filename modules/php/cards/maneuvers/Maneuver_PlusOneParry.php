<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_PlusOneParry extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Parry");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id) {
            $owner = $this->getOwningCard($event->theah);
            $event->parry += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Parry."), $owner->getInjectCode());
        }
    }
}