<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01139 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+X Thrust. X = Actor's Base Combat Value");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner instanceof _01139)
            {
                $owner->goToLocker = true;
                $owner->IsUpdated = true;
            }


            $actor = $event->theah->getCharacterById($event->actorId);
            $event->thrust += $actor->Combat;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Maneuver [%s] adds +%d Thrust."), $owner->getInjectCode(), $this->Name, $actor->Combat);
        }
    }
}