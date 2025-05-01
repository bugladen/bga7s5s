<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_01186 extends Technique
{
    public bool $CancelOpponentManeuvers;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Marayam Benu Pleroma: No Adversary Maneuvers";

        $this->CancelOpponentManeuvers = false;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        // If activated then this technique will cancel any Maneuvers that are attempted by the opponent
        // until the start of the next round for the owning character.
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $this->CancelOpponentManeuvers = true;
            $this->Used = true;
            $maryam = $this->getOwningCard($event->theah);
            $maryam->IsUpdated = true;
        }

        // If the event is a new round and the owning character is Maryam then reset the CancelOpponentManeuvers flag
        if ($event instanceof EventDuelNewRound && $event->actorId == $this->getOwningCard($event->theah)->Id)
        {
            $this->CancelOpponentManeuvers = false;
            $maryam = $this->getOwningCard($event->theah);
            $maryam->IsUpdated = true;
        }

        // If the duel is over then reset the CancelOpponentManeuvers flag
        if ($event instanceof EventDuelEnd)
        {
            $this->CancelOpponentManeuvers = false;
            $maryam = $this->getOwningCard($event->theah);
            $maryam->IsUpdated = true;
        }
    }

    public function eventCheck(Event $event)
    {
        if ($event instanceof EventResolveManeuver && $this->CancelOpponentManeuvers)
        {
            throw new \BgaUserException("Technique of Maryam Benu Pleroma is active. Opponent Maneuvers are prevented this round.");
        }
    }
}