<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_DestroyPlusOneThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_01157 extends Technique_DestroyPlusOneThrust implements IRangedAbility
{
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $event->actorId);
            $event->theah->queueEvent($rangedAbilityPlayedEvent);
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $event->actorId);
            $event->theah->queueEvent($rangedAbilityPlayedEvent);
        }
    }
}
