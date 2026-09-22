<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_04060 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Thrust, or +1 Riposte if Uncontrolled");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            // WHY: "this location" = duel site. Actor stays at the city location even if
            // the adversary was destroyed earlier in the round (same as Maneuver_01110 / C.10).
            $location = $actor->Location;
            $uncontrolled = $event->theah->game->getControllerForLocation($location) == 0;

            // WHY: "instead" = exclusive branch — uncontrolled → Riposte only; else Thrust only.
            // Not C.10 (always Riposte + optional claim/unclaim).
            if ($uncontrolled)
            {
                $event->riposte += 1;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s adds 1 Riposte (location is uncontrolled)."),
                    $owner->getInjectCode()
                );
            }
            else
            {
                $event->thrust += 1;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s adds 1 Thrust."),
                    $owner->getInjectCode()
                );
            }
        }
    }
}
