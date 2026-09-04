<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
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
        if (! $owner)
        {
            return false;
        }
        if ($playerId != $owner->ControllerId)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed    

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->thrust += 1;
            $event->explanations[] = sprintf(clienttranslate("%s is destroyed and adds +1 Thrust"), $owner->getInjectCode());

            $this->queueDestroyOwner($event->theah, $owner);
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->adversaryThreat += 1;
            $event->explanations[] = sprintf(clienttranslate("%s is destroyed and adds +1 Threat"), $owner->getInjectCode());

            // WHY: Challenge reject still runs GenerateChallengeThreat (rejection-wound
            // math). The old code always did getOwningCharacter()->Id with no attach
            // guard — unlike the duel branch — so an unattached owner (or a Character
            // copy with no attached host) fataled here on zombie refuse.
            $this->queueDestroyOwner($event->theah, $owner);
        }
    }

    /**
     * Destroy the host attachment: unequip if needed, then discard from play.
     * WHY skip non-Attachment owners: Katain/Yepikhodov copies set OwnerId to the
     * Character; "Destroy this card" must not unequip/discard that Character.
     * WHY use AttachedToId not getOwningCharacter(): avoids null->Id when the
     * attach target is missing from Theah while AttachedToId is still set.
     */
    private function queueDestroyOwner(Theah $theah, ?Card $owner): void
    {
        if (! ($owner instanceof Attachment))
        {
            return;
        }

        if ($owner->isAttached())
        {
            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($owner->ControllerId, $owner->AttachedToId, $owner->Id);
            $theah->queueEvent($unequipEvent);
        }

        $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($owner->OwnerId, $owner->Id, $owner->Location, $owner->Id);
        $theah->queueEvent($discardEvent);
    }
}
