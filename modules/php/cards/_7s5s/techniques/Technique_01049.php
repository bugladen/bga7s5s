<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01049 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Gain Lethal");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        $owner = $this->getOwningCard($theah);
        return ! $owner->Engaged;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $engageEvent = EventFactory::createCardEngagedEvent($event->playerId, $owner->Id, $owner->Id, $this->Id);
            $event->theah->queueEvent($engageEvent);
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $event->adversaryThreatIsLethal = true;
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $challengerId = $event->theah->getDuelChallengerId();
            $defenderId = $event->theah->getDuelDefenderId();

            $challengerThreatIsLethal = $event->actorId == $challengerId ? null : true;
            $defenderThreatIsLethal = $event->actorId == $defenderId ? null : true;
        
            $lethalEvent = EventFactory::createThreatModifiedEvent(0, 0, $challengerThreatIsLethal, $defenderThreatIsLethal);
            $event->theah->queueEvent($lethalEvent);
        }
    } 
}