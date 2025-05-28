<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01135 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "+2 Parry, or Wound Adversary and Give -2 Thrust Next Round";
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id) {
            $event->parry += 2;
        }
    }
}