<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGetCostForManeuverFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01086 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage or Wound Adversary");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelGetCostForManeuverFromHand && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getCharacterById($event->adversaryId);

            if ($adversary->hasTrait("Mercenary"))
            {
                $event->discount += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s reduces the cost of Maneuver by 1 because your Adversary is a Mercenary."), $owner->Name);
            }
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $adversary = $event->theah->getCharacterById($event->adversaryId);
            $owner = $this->getOwningCard($event->theah);
            if (! $adversary->Engaged)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($event->playerId, $event->adversaryId, $owner->Id);
                $event->theah->queueEvent($engageEvent);
            }
            else
            {
                $woundEvent = EventFactory::createCharacterWoundedEvent($event->adversaryId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $event->theah->queueEvent($woundEvent);
            }
        }
    }
}