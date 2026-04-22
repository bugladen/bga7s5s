<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_DestroyPlusOneThrust extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Destroy and +1 Thrust");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($playerId != $owner->ControllerId)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->thrust += 1;
            $event->explanations[] = sprintf(clienttranslate("%s is destroyed and adds +1 Thrust"), $owner->getInjectCode());

            if ($owner instanceof Attachment && $owner->isAttached())
            {
                $character = $this->getOwningCharacter($event->theah);
                $unequipEvent = EventFactory::createAttachmentUnequippedEvent($owner->ControllerId, $character->Id, $owner->Id);
                $event->theah->queueEvent($unequipEvent);
    
                $owner = $this->getOwningCard($event->theah);
                $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($owner->OwnerId, $owner->Id, $owner->Location, $owner->Id);
                $event->theah->queueEvent($discardEvent);
            }
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->adversaryThreat += 1;
            $event->explanations[] = sprintf(clienttranslate("%s is destroyed and adds +1 Threat"), $owner->getInjectCode());

            $character = $this->getOwningCharacter($event->theah);
            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($owner->ControllerId, $character->Id, $owner->Id);
            $event->theah->queueEvent($unequipEvent);

            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($owner->OwnerId, $owner->Id, $owner->Location, $owner->Id);
            $event->theah->queueEvent($discardEvent);
        }
    }
}