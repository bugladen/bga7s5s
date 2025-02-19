<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01135 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Mireli's Revision: +2 Parry, or wound adversary and give -2 Thrust next round";
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id) {
            $event->parry += 2;
        }
    }
}