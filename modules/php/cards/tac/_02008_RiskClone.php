<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;

class _02008_RiskClone extends FactionAttachment
{
    public int $ClonedCardId = 0;
    public int $TargetCharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->FaceDown = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardDiscardedFromPlay && $event->cardId == $this->Id)
        {
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($event->ownerId, $this->Id, $messageHidden = true, $permanentlyHide = true);
            $event->theah->queueEvent($removeEvent);
        }

        // WHY: Card text says "When that character is destroyed, put that risk into your hand."
        // Must trigger on character destruction specifically, not on any discard of the clone.
        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->TargetCharacterId)
        {
            $addEvent = EventFactory::createCardAddedToHandEvent($this->ControllerId, $this->ClonedCardId, $hidden = true);
            $event->theah->queueEvent($addEvent);
        }
    }

}
