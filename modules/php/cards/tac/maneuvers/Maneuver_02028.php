<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_02028 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+X Thrust (X = Influence), Diplomat gains Lethal");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getCharacterById($event->actorId);
            $thrustBonus = $actor->ModifiedInfluence;
            $event->thrust += $thrustBonus;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Maneuver [%s] adds +%d Thrust."), $owner->getInjectCode(), $this->Name, $thrustBonus);
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();

            if ($actor->hasTrait("Diplomat"))
            {
                $lethalEvent = EventFactory::createGainLethalEvent($actor->Id, $event->theah);
                $event->theah->queueEvent($lethalEvent);
            }
        }
    }
}
