<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_01155 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Destroy and +1 Thrust");
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->thrust += 1;
            $event->explanations[] = sprintf(clienttranslate("%s is destroyed and adds +1 Thrust"), $owner->getInjectCode());

            $character = $this->getOwningCharacter($event->theah);
            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($owner->ControllerId, $character->Id, $owner->Id);
            $event->theah->queueEvent($unequipEvent);

            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($owner->ControllerId, $owner->Id, $owner->Location);
            $event->theah->queueEvent($discardEvent);
        }
    }
}