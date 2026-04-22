<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_DestroyPlusOneThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01157 extends Technique_DestroyPlusOneThrust implements IRangedAbility
{
    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))        
        {
            return false;
        }

        $owner = $this->getOwningAttachment($theah);
        return $owner && $owner->isAttached();            
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            $rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $actor->Id);
            $event->theah->queueEvent($rangedAbilityPlayedEvent);
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningAttachment($event->theah);
            $character = $this->getOwningCharacter($event->theah);
            $rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $character->Id);
            $event->theah->queueEvent($rangedAbilityPlayedEvent);
        }
    }
}