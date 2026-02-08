<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01057 extends Maneuver implements IRangedAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gain Lethal");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            $challengerId = $event->theah->getDuelChallengerId();
            $defenderId = $event->theah->getDuelDefenderId();

            $challengerThreatIsLethal = $actor->Id == $challengerId ? null : true;
            $defenderThreatIsLethal = $actor->Id == $defenderId ? null : true;
        
            $lethalEvent = EventFactory::createThreatModifiedEvent(0, 0, $challengerThreatIsLethal, $defenderThreatIsLethal);
            $event->theah->queueEvent($lethalEvent);

            $owner = $this->getOwningCard($event->theah);
            $rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $actor->Id);
            $event->theah->queueEvent($rangedAbilityPlayedEvent);
        }
    }
}